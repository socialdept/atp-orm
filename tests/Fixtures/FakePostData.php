<?php

namespace SocialDept\AtpOrm\Tests\Fixtures;

use SocialDept\AtpSchema\Data\Data;

class FakePostData extends Data
{
    public function __construct(
        public readonly string $text,
        public readonly string $createdAt,
        public readonly ?array $langs = null,
    ) {
    }

    public static function getLexicon(): string
    {
        return 'app.bsky.feed.post';
    }

    public static function fromArray(array $data): static
    {
        return new static(
            text: $data['text'],
            createdAt: $data['createdAt'],
            langs: $data['langs'] ?? null,
        );
    }
}
