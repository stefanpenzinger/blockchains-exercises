<?php

namespace Models;

class BlockChain
{
    private array $chain;
    private array $currentTransactions;

    public function __construct()
    {
        $this->chain = [];
        $this->currentTransactions = [];

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
}
