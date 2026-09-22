<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Wallet;
use App\Enum\Currency;
use App\Exception\WalletAlreadyExistsException;
use App\Repository\WalletRepositoryInterface;
use App\Exception\WalletNotFoundException;
use App\Repository\TransactionRepositoryInterface;

readonly class WalletService
{
    public function __construct(
        private WalletRepositoryInterface $walletRepository,
        private TransactionRepositoryInterface $transactionRepository,
    ) {
    }

    public function createWallet(int $userId, Currency $currency): Wallet
    {
        $existing = $this->walletRepository->findByUserIdAndCurrency($userId, $currency);

        if (null !== $existing) {
            throw new WalletAlreadyExistsException($userId, $currency);
        }

        $wallet = Wallet::create($userId, $currency);
        $this->walletRepository->save($wallet);

        return $wallet;
    }

    public function deleteWallet(int $userId, int $walletId): void
    {
        $wallet = $this->walletRepository->findById($walletId);

        if (null === $wallet) {
            throw new WalletNotFoundException($walletId);
        }

        if ($wallet->getUserId() !== $userId) {
            throw new \Exception('No user found.');
        }

        if($wallet->getBalance() > 0) {
            throw new \Exception('Wallet has positive balance. Transfer balance to a different wallet to delete.');
        }

        if (!empty($this->transactionRepository->findByWalletId($walletId))) {
            throw new \Exception('Cannot delete wallet with transaction history.');
        }

        $this->walletRepository->delete($wallet);
    }
}
