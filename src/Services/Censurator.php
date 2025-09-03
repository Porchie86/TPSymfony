<?php

namespace App\Services;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class Censurator
{
    private array $offensiveWords;

    public function __construct(ParameterBagInterface $params)
    {
        $filePath = $params->get('offensive_words_file');
        $this->offensiveWords = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }

    public function purify(string $string): string
    {
        foreach ($this->offensiveWords as $word) {
            $pattern = '/\b' . preg_quote($word, '/') . '(?=\s|$|\W)/iu';
            $replacement = str_repeat('*', mb_strlen($word));
            $string = preg_replace($pattern, $replacement, $string);
        }

        return $string;
    }
}
