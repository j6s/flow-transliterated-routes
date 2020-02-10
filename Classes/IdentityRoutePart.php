<?php declare(strict_types=1);

namespace J6s\TransliteratedRoutes;

use Behat\Transliterator\Transliterator;
use Neos\Flow\Mvc\Routing\IdentityRoutePart as FlowIdentityRoutePart;

class IdentityRoutePart extends FlowIdentityRoutePart
{

    /** @var array<string, string> */
    protected $replacements = [
        'ä' => 'ae',
        'Ä' => 'Ae',
        'ö' => 'oe',
        'Ö' => 'Oe',
        'ü' => 'ue',
        'Ü' => 'Ue',
        'ß' => 'ss',
    ];

    /**
     * @param mixed $value
     * @return string
     */
    protected function rewriteForUri($value)
    {
        if (!$this->canBeCastToString($value)) {
            return '';
        }

        $value = strtr((string) $value, $this->replacements);
        $value = $this->transliterate($value);

        // Retain original behaviour
        return parent::rewriteForUri($value);
    }

    private function transliterate(string $text): string
    {
        if (preg_match('/[\x80-\xff]/', $text) && Transliterator::validUtf8($text)) {
            $text = Transliterator::utf8ToAscii($text);
        }

        return $text;
    }

    /** @param mixed $value */
    private function canBeCastToString($value): bool
    {
        return \is_scalar($value)
            || (\is_object($value) && method_exists($value, '__toString'));
    }

    /** @param array<string, mixed> $options */
    public function setOptions(array $options): void
    {
        parent::setOptions($options);
        $this->uriPattern = $options['uriPattern'];
        $this->objectType = $options['objectType'];

        if (array_key_exists('replacements', $options)) {
            $this->replacements = $options['replacements'];
        }
    }
}
