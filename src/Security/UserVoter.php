<?php

namespace App\Security;

use App\Entity\Client;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserVoter extends Voter
{
    const VIEW = 'view';
    const EDIT = 'edit';
    const DELETE = 'delete';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])) {
            return false;
        }

        if (!$subject instanceof User) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $client = $token->getUser();

        if (!$client instanceof Client) {
            return false;
        }

        $user = $subject;

        return match($attribute) {
            self::VIEW => $this->canView($user, $client),
            self::EDIT => $this->canEdit($user, $client),
            self::DELETE => $this->canDelete($user, $client),
            default => throw new \LogicException('This code should not be reached!')
        };
    }

    private function canView(User $user, Client $client): bool
    {
        return $client === $user->getClient();
    }

    private function canEdit(User $user, Client $client): bool
    {
        return $client === $user->getClient();
    }

    private function canDelete(User $user, Client $client): bool
    {
        return $client === $user->getClient();
    }
}
