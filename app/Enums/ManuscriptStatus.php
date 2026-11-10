<?php

namespace App\Enums;

enum ManuscriptStatus: string
{
    case Submitted = 'submitted';
    case InitialScreening = 'initial_screening';
    case RevisionRequested = 'revision_requested';
    case UnderReview = 'under_review';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';
    case Archived = 'archived';

    /** @return list<self> */
    public function next(): array
    {
        return match ($this) {
            self::Submitted => [self::InitialScreening, self::Withdrawn],
            self::InitialScreening => [self::UnderReview, self::RevisionRequested, self::Rejected],
            self::RevisionRequested => [self::UnderReview, self::Rejected, self::Withdrawn],
            self::UnderReview => [self::RevisionRequested, self::Accepted, self::Rejected],
            self::Accepted, self::Rejected, self::Withdrawn => [self::Archived],
            self::Archived => [],
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Accepted, self::Rejected, self::Withdrawn, self::Archived], true);
    }
}
