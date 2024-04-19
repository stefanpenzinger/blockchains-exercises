<?php

use DI\Container;
use Middleware\JsonMiddleware;
use Models\BlockChain;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Ramsey\Uuid\Uuid;
use Slim\Factory\AppFactory;

require_once __DIR__ . '/../vendor/autoload.php';

session_start();

$SERVER_PORT = "SERVER_PORT";
$BLOCKCHAIN_ID = "Blockchain" . $_SERVER[$SERVER_PORT];
$NODE_IDENTIFIER = "NodeId" . $_SERVER[$SERVER_PORT];
$BLOCKCHAIN_FILE_PATH = __DIR__ . '/../storage/' . $BLOCKCHAIN_ID;
$NODE_IDENTIFIER_FILE_PATH = __DIR__ . '/../storage/' . $NODE_IDENTIFIER;

$container = new Container();
AppFactory::setContainer($container);
$app = AppFactory::create();
$app->addMiddleware(new JsonMiddleware());


if (file_exists($BLOCKCHAIN_FILE_PATH)) {
    $serializedBlockchain = file_get_contents($BLOCKCHAIN_FILE_PATH);
    $blockchain = unserialize($serializedBlockchain);
} else {
    $blockchain = new BlockChain();
}

$container->set($BLOCKCHAIN_ID, $blockchain);

if (file_exists($NODE_IDENTIFIER_FILE_PATH)) {
    $serializedNodeIdentifier = file_get_contents($NODE_IDENTIFIER_FILE_PATH);
    $nodeIdentifier = unserialize($serializedNodeIdentifier);
} else {
    $nodeIdentifier = str_replace('-', '', Uuid::uuid4()->toString());
    $serializedNodeIdentifier = serialize($nodeIdentifier);
    file_put_contents($NODE_IDENTIFIER_FILE_PATH, $serializedNodeIdentifier);
}

$container->set($NODE_IDENTIFIER, $nodeIdentifier);

$app->get('/mine', function (Request $request, Response $response)
    use ($BLOCKCHAIN_ID, $NODE_IDENTIFIER, $BLOCKCHAIN_FILE_PATH) {
    /** @var BlockChain $blockchain */
    $blockchain = $this->get($BLOCKCHAIN_ID);
    $nodeIdentifier = $this->get($NODE_IDENTIFIER);

    $lastBlock = $blockchain->lastBlock();
    $lastProof = $lastBlock['proof'];
    $proof = $blockchain->proofOfWork($lastProof);

    $blockchain->newTransaction('0', $nodeIdentifier, 1);
    $previousHash = $blockchain->hash($lastBlock);
    $block = $blockchain->newBlock($proof, $previousHash);

    $body = [
        'message' => "New Block Forged",
        'index' => $block['index'],
        'transactions' => $block['transactions'],
        'proof' => $block['proof'],
        'previous_hash' => $block['previous_hash']
    ];

    $serializedBlockchain = serialize($blockchain);
    file_put_contents($BLOCKCHAIN_FILE_PATH, $serializedBlockchain);

    $response->getBody()->write(json_encode($body, JSON_PRETTY_PRINT));
    return $response;
});

$app->post('/transactions/new', function (Request $request, Response $response)
    use ($BLOCKCHAIN_ID, $BLOCKCHAIN_FILE_PATH) {
    /** @var BlockChain $blockchain */
    $blockchain = $this->get($BLOCKCHAIN_ID);
    $params = (array)$request->getParsedBody();
    $required = ['sender', 'recipient', 'amount'];
    $errors = array();

    foreach ($required as $field) {
        if (!isset($params[$field])) {
            $errors[] = "Missing field: $field";
        }
    }

    if (!empty($errors)) {
        $response->getBody()->write(json_encode(['errors' => $errors], JSON_PRETTY_PRINT));
        return $response->withStatus(400);
    }

    $index = $blockchain->newTransaction($params['sender'], $params['recipient'], $params['amount']);
    $body = ['message' => "Transaction will be added to block $index."];

    $serializedBlockchain = serialize($blockchain);
    file_put_contents($BLOCKCHAIN_FILE_PATH, $serializedBlockchain);

    $response->getBody()->write(json_encode($body, JSON_PRETTY_PRINT));
    return $response->withStatus(201);
});

$app->get('/chain', function (Request $request, Response $response) use ($BLOCKCHAIN_ID) {
    /** @var BlockChain $blockchain */
    $blockchain = $this->get($BLOCKCHAIN_ID);
    $body = ['chain' => $blockchain->chain(), 'length' => count($blockchain->chain())];
    $response->getBody()->write(json_encode($body, JSON_PRETTY_PRINT));
    return $response;
});

$app->post('/nodes/register', function (Request $request, Response $response)
    use ($BLOCKCHAIN_ID, $BLOCKCHAIN_FILE_PATH) {
    /** @var BlockChain $blockchain */
    $blockchain = $this->get($BLOCKCHAIN_ID);
    $body = $request->getBody()->getContents();
    $data = json_decode($body, true);

    if (!isset($data['nodes'])) {
        $body = ['error' => 'Please supply a valid list of nodes'];
        $response->getBody()->write(json_encode($body, JSON_PRETTY_PRINT));
        return $response->withStatus(400);
    }

    foreach ($data['nodes'] as $url) {
        $blockchain->registerNode($url);
    }

    $serializedBlockchain = serialize($blockchain);
    file_put_contents($BLOCKCHAIN_FILE_PATH, $serializedBlockchain);

    $body = [
        'message' => 'New nodes have been added',
        'total_nodes' => $blockchain->nodes()
    ];
    $response->getBody()->write(json_encode($body, JSON_PRETTY_PRINT));
    return $response->withStatus(201);
});

$app->get('/nodes/resolve', function (Request $request, Response $response)
    use ($BLOCKCHAIN_ID, $BLOCKCHAIN_FILE_PATH) {
    /** @var BlockChain $blockchain */
    $blockchain = $this->get($BLOCKCHAIN_ID);
    $replaced = $blockchain->resolveConflicts();

    if ($replaced) {
        $body = [
            'message' => 'Our chain was replaced',
            'new_chain' => $blockchain->chain()
        ];

        $serializedBlockchain = serialize($blockchain);
        file_put_contents($BLOCKCHAIN_FILE_PATH, $serializedBlockchain);
    } else {
        $body = [
            'message' => 'Our chain is authoritative',
            'chain' => $blockchain->chain()
        ];
    }

    $response->getBody()->write(json_encode($body, JSON_PRETTY_PRINT));
    return $response;
});

$app->run();