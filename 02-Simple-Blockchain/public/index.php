<?php

use Middleware\JsonMiddleware;
use Models\BlockChain;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Ramsey\Uuid\Uuid;
use Slim\Factory\AppFactory;

require_once __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();
$app->addMiddleware(new JsonMiddleware());

$nodeIdentifier = str_replace('-', '', Uuid::uuid4()->toString());
$blockchain = new BlockChain();

$app->get('/mine', function (Request $request, Response $response) use ($blockchain, $nodeIdentifier) {
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
    $response->getBody()->write(json_encode($body, JSON_PRETTY_PRINT));
    return $response;
});

$app->post('/transactions/new', function (Request $request, Response $response) use ($blockchain) {
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
    $response->getBody()->write(json_encode($body, JSON_PRETTY_PRINT));
    return $response->withStatus(201);
});

$app->get('/chain', function (Request $request, Response $response) use ($blockchain) {
    $body = ['chain' => $blockchain->chain(), 'length' => count($blockchain->chain())];
    $response->getBody()->write(json_encode($body, JSON_PRETTY_PRINT));
    return $response;
});

$app->post('/nodes/register', function (Request $request, Response $response) use ($blockchain) {
    $params = (array)$request->getParsedBody();
    $nodes = $params['nodes'] ?? [];

    if (empty($nodes)) {
        $body = ['error' => 'Please supply a valid list of nodes'];
        $response->getBody()->write(json_encode($body, JSON_PRETTY_PRINT));
        return $response->withStatus(400);
    }

    foreach ($nodes as $node) {
        $blockchain->registerNode($node);
    }

    $body = [
        'message' => 'New nodes have been added',
        'total_nodes' => $blockchain->nodes()
    ];
    $response->getBody()->write(json_encode($body, JSON_PRETTY_PRINT));
    return $response->withStatus(201);
});

$app->get('/nodes/resolve', function (Request $request, Response $response) use ($blockchain) {
    $replaced = $blockchain->resolveConflicts();

    if ($replaced) {
        $body = [
            'message' => 'Our chain was replaced',
            'new_chain' => $blockchain->chain()
        ];
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