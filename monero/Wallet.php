<?php

namespace Monero;

use App\Project;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class Wallet
{
    /**
     * Wallet constructor.
     *
     * @param null $client
     */
    public function __construct($client = null)
    {
        $this->client = $client ?: new jsonRPCClient(env('RPC_URL'));
    }

    /**
     * Gets a Payment address for receiving payments
     *
     * @return array
     *
     * @internal param \Wallet $wallet
     */
    public function getPaymentAddress()
    {

        $subaddress = $this->createSubaddress();
        if (!$subaddress) {
            return ['address' => 'not valid', 'expiration_time' => 900];
        }
        $project = new Project();
        $project->subaddr_index = $subaddress['address_index'];
        $project->save();

        return ['address' => $subaddress['address'], 'subaddr_index' => $subaddress['address_index']];
    }

    /**
     * Returns the actual available and useable balance (unlocked balance)
     *
     * @return float|int|mixed
     */
    public function balance()
    {
        return $this->client->balance();
    }

    /**
     * @param $min_height
     * @param $account_index
     *
     * @return \Illuminate\Support\Collection
     */
    public function scanIncomingTransfers($min_height = 0, $account_index = 0)
    {
        $response = $this->client->incomingTransfers($min_height);
        if (!$response) {
            return collect([]);
        }

        $transactions = [];
        const toScan = ['pool', 'in'];
        foreach (toScan as $entry) {
            if (!isset($response[$entry])) {
                continue;
            }
            foreach ($response[$entry] as $payment) {
                if $payment['subaddr_index']['major'] != $account_index {
                    continue;
                }
                if ($payment['locked']) {
                    continue;
                }
                $transaction = new Transaction(
                    $payment['txid'],
                    $payment['amount'],
                    $payment['address'],
                    $payment['confirmations'],
                    0,
                    Carbon::now(),
                    $payment['subaddr_index']['minor'],
                    $payment['height']
                );
                $transactions[] = $transaction;
            }
        }

        return collect($transactions);
    }

    /**
     * Gets the current blockheight of xmr
     *
     * @return int
     */
    public function blockHeight()
    {
        return $this->client->blockHeight();
    }

    /**
     * Returns monero wallet address
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->client->address();
    }

    /**
     * Returns XMR subaddress
     *
     * @return mixed
     */
    public function createSubaddress()
    {
        return $this->client->createSubaddress();
    }

    /**
     * @param $address
     * @param $amount
     *
     * @return string
     */
    public function createQrCodeString($address, $amount): string
    {
        return 'monero:'.$address.'?tx_amount='.$amount;
    }

    /**
     * gets all the subaddr_indexes outstanding from the address_pool, we use these to check against the latest mined blocks
     *
     * @return Collection
     */
    public function getSubaddressIndexes()
    {

        return Project::pluck('subaddr_index'); //stop scanning for subaddr_index after 24h
    }
}
