<?php

namespace Models;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Ramsey\Collection\Set;
use Slim\Logger;

class BlockChain
{
    private array $chain;
    private array $currentTransactions;
    private Set $nodes;

    public function __construct()
    {
        $this->chain = [];
        $this->currentTransactions = [];
        $this->nodes = new Set(Node::class);

        // Create Genesis block
        $this->newBlock(100, '1');
    }

    /** Create a new block and add it to the chain
     * @param $proof int
     * @param $previousHash string|null
     * @return array block
     */
    public function newBlock(int $proof, string $previousHash = null): array
    {
        $block = [
            'index' => count($this->chain) + 1,
            'timestamp' => time(),
            'transactions' => $this->currentTransactions,
            'proof' => $proof,
            'previous_hash' => $previousHash ?? $this->hash(end($this->chain))
        ];

        $this->currentTransactions = [];
        $this->chain[] = $block;

        return $block;
    }

    /** Hash a block
     * @param $block array
     * @return string hash
     */
    public static function hash(array $block): string
    {
        $blockString = json_encode($block);
        return hash('sha256', $blockString);
    }

    /**
     * @param $sender string
     * @param $recipient string
     * @param $amount int
     * @return int|mixed index of the block that will hold this transaction
     */
    public function newTransaction(string $sender, string $recipient, int $amount): mixed
    {
        $this->currentTransactions[] = [
            'sender' => $sender,
            'recipient' => $recipient,
            'amount' => $amount
        ];

        return $this->lastBlock()['index'] + 1;
    }

    public function lastBlock()
    {
        return end($this->chain);
    }

    /** Simple proof of work algorithm:
     * @param $lastProof int
     * @return int proof
     */
    public function proofOfWork(int $lastProof): int
    {
        $proof = 0;
        while (!$this->validProof($lastProof, $proof)) {
            $proof++;
        }

        return $proof;
    }

    /** Check if the hash of the proof and the previous proof contains 4 leading zeros
     * @param $lastProof int
     * @param $proof int
     * @return bool valid
     */
    public function validProof($lastProof, $proof): bool
    {
        $guess = "{$lastProof}{$proof}";
        $hash = hash('sha256', $guess);

        return str_starts_with($hash, '0000');
    }

    /** Return the full chain
     * @return array chain
     */
    public function chain(): array
    {
        return $this->chain;
    }

    /** Add a new node to the list of nodes
     * @param Node $node
     */
    public function registerNode(Node $node): void
    {
        $this->nodes->add($node);
    }

    /** Return the set of nodes
     * @return Set
     */
    public function nodes(): Set
    {
        return $this->nodes;
    }

    /** Replace chain with the longest chain of every node
     * @throws GuzzleException
     */
    public function resolveConflicts(): bool
    {
        $client = new Client();
        $neighbors = $this->nodes;
        $newChain = null;
        $maxLen = count($this->chain);

        /** @var Node $node */
        foreach ($neighbors as $node) {
            $url = $node->url() . "/chain";
            $res = $client->request('GET', $url);

            if ($res->getStatusCode() !== 200) {
                continue;
            }

            $data = json_decode($res->getBody(), true);
            $length = $data['length'];
            $chain = $data['chain'];

            if ($length > $maxLen && $this->validChain($chain)) {
                $maxLen = $length;
                $newChain = $chain;
            }
        }

        if ($newChain) {
            $this->chain = $newChain;
            return true;
        }

        return false;
    }

    /** Determine if a given blockchain is valid
     * @param array $chain A blockchain
     * @return bool True if valid, false if not
     */
    private function validChain(array $chain): bool
    {
        $lastBlock = $chain[0];
        $currentIndex = 1;

        while ($currentIndex < count($this->chain)) {
            $block = $chain[$currentIndex];

            Logger::class->info($lastBlock);
            Logger::class->info($block);
            Logger::class->info("------------------");

            if ($block['previous_hash'] !== $this->hash($lastBlock)) {
                return false;
            }

            if (!$this->validProof($lastBlock['proof'], $block['proof'])) {
                return false;
            }

            $lastBlock = $block;
            $currentIndex++;
        }

        return true;
    }
}
