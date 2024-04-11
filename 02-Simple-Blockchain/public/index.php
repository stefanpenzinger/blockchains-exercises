<?php

use Models\BlockChain;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Ramsey\Uuid\Uuid;
use Slim\Factory\AppFactory;

require_once __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();
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
    $params = (array) $request->getParsedBody();
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

$app->run();