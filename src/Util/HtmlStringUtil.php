<?php

declare(strict_types=1);

namespace Evirma\Bundle\EssentialsBundle\Util;

use DOMDocument;
use DOMElement;
use DOMLettersIterator;
use DOMNode;
use DOMText;
use DOMWordsIterator;

final class HtmlStringUtil
{
    final private function __construct()
    {}

    public static function truncateHtml(string $html, int $lengthOrWordCount = 30, bool $isWords = false, string $separator = '...'): string
    {
        return $isWords ? self::truncateHtmlWords($html, $lengthOrWordCount, '') : self::truncateHtmlLetters($html, $lengthOrWordCount, $separator);
    }

    private static function truncateHtmlWords(string $html, int $limit = 0, string $ellipsis = '...'): string
    {
        if ($limit <= 0) {
            return $html;
        }

        $dom = self::htmlToDomDocument($html);

        // Grab the body of our DOM.
        $body = $dom->getElementsByTagName("body")->item(0);

        // Iterate over words.
        $words = new DOMWordsIterator($body);

        /** @noinspection PhpUnusedLocalVariableInspection */
        foreach ($words as $null) {

            // If we have exceeded the limit, we delete the remainder of the content.
            if ($words->key() >= $limit) {

                // Grab current position.
                $currentWordPosition = $words->currentWordPosition();
                $curNode = $currentWordPosition[0];
                $offset = $currentWordPosition[1];
                $words = $currentWordPosition[2];

                $curNode->nodeValue = substr(
                    $curNode->nodeValue,
                    0,
                    $words[$offset][1] + strlen($words[$offset][0])
                );

                self::removeProceedingNodes($curNode, $body);

                if (!empty($ellipsis)) {
                    self::insertEllipsis($curNode, $ellipsis);
                }

                break;
            }

        }

        return $dom->saveHTML();
    }

    private static function htmlToDomDocument(string $html): DOMDocument
    {
        // Transform multibyte entities which otherwise display incorrectly.
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');

        // Internal errors enabled as HTML5 not fully supported.
        libxml_use_internal_errors(true);

        // Instantiate new DOMDocument object, and then load in UTF-8 HTML.
        $dom = new DOMDocument();
        $dom->encoding = 'UTF-8';
        $dom->loadHTML($html);

        return $dom;
    }

    private static function removeProceedingNodes(DOMNode|DOMElement $domNode, DOMNode|DOMElement $topNode): void
    {
        $nextNode = $domNode->nextSibling;

        if ($nextNode !== null) {
            self::removeProceedingNodes($nextNode, $topNode);
            $domNode->parentNode->removeChild($nextNode);
        } else {
            //scan upwards till we find a sibling
            $curNode = $domNode->parentNode;
            while ($curNode !== $topNode) {
                if ($curNode->nextSibling !== null) {
                    $curNode = $curNode->nextSibling;
                    self::removeProceedingNodes($curNode, $topNode);
                    $curNode->parentNode->removeChild($curNode);
                    break;
                }
                $curNode = $curNode->parentNode;
            }
        }
    }

    private static function insertEllipsis(DOMNode|DOMElement $domNode, string $ellipsis): void
    {
        $avoid = ['a', 'strong', 'em', 'h1', 'h2', 'h3', 'h4', 'h5']; //html tags to avoid appending the ellipsis to

        if (in_array($domNode->parentNode->nodeName, $avoid) && $domNode->parentNode->parentNode !== null) {
            // Append as text node to parent instead
            $textNode = new DOMText($ellipsis);

            if ($domNode->parentNode->parentNode->nextSibling) {
                $domNode->parentNode->parentNode->insertBefore($textNode, $domNode->parentNode->parentNode->nextSibling);
            } else {
                $domNode->parentNode->parentNode->appendChild($textNode);
            }

        } else {
            // Append to current node
            $domNode->nodeValue = rtrim($domNode->nodeValue).$ellipsis;
        }
    }

    /**
     * Safely truncates HTML by a given number of letters.
     *
     * @param string  $html     Input HTML.
     * @param integer $limit    Limit to how many letters we preserve.
     * @param string  $ellipsis String to use as ellipsis (if any).
     * @return string            Safe truncated HTML.
     */
    private static function truncateHtmlLetters(string $html, int $limit = 0, string $ellipsis = ""): string
    {
        if ($limit <= 0) {
            return $html;
        }

        $dom = self::htmlToDomDocument($html);

        // Grab the body of our DOM.
        $body = $dom->getElementsByTagName("body")->item(0);

        // Iterate over letters.
        $letters = new DOMLettersIterator($body);

        /** @noinspection PhpUnusedLocalVariableInspection */
        foreach ($letters as $letter) {
            // If we have exceeded the limit, we want to delete the remainder of this document.
            if ($letters->key() >= $limit) {

                $currentText = $letters->currentTextPosition();
                $currentText[0]->nodeValue = substr($currentText[0]->nodeValue, 0, $currentText[1] + 1);
                self::removeProceedingNodes($currentText[0], $body);

                if (!empty($ellipsis)) {
                    self::insertEllipsis($currentText[0], $ellipsis);
                }

                break;
            }
        }

        return $dom->saveHTML();
    }
}
