<?php

namespace App\Content;

use DOMDocument;
use DOMElement;
use DOMNode;

class RichTextSanitizer
{
    /** @var list<string> */
    private array $allowedTags = ['p', 'br', 'strong', 'b', 'em', 'i', 'ul', 'ol', 'li', 'a', 'h2', 'h3', 'blockquote', 'figure', 'img', 'figcaption', 'hr'];

    public function sanitize(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8" ?><div id="ezyweb-rich-text">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $root = $document->getElementById('ezyweb-rich-text');
        if (! $root instanceof DOMElement) {
            return '';
        }

        $this->clean($root);

        return collect(iterator_to_array($root->childNodes))->map(fn (DOMNode $node): string => (string) $document->saveHTML($node))->implode('');
    }

    private function clean(DOMNode $parent): void
    {
        foreach (array_reverse(iterator_to_array($parent->childNodes)) as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }
            $tag = strtolower($node->tagName);
            if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed'], true)) {
                $parent->removeChild($node);

                continue;
            }
            if (! in_array($tag, $this->allowedTags, true)) {
                $this->clean($node);
                while ($node->firstChild) {
                    $parent->insertBefore($node->firstChild, $node);
                }
                $parent->removeChild($node);

                continue;
            }

            $href = $tag === 'a' ? $node->getAttribute('href') : '';
            $src = $tag === 'img' ? $node->getAttribute('src') : '';
            $alt = $tag === 'img' ? $node->getAttribute('alt') : '';
            $imageWidth = $tag === 'figure' ? $node->getAttribute('data-width') : '';
            $imageAlign = $tag === 'figure' ? $node->getAttribute('data-align') : '';
            while ($node->attributes->length > 0) {
                $attribute = $node->attributes->item(0);
                if ($attribute) {
                    $node->removeAttributeNode($attribute);
                }
            }
            if ($tag === 'a' && preg_match('/^(?:https?:\/\/|mailto:|tel:|\/|#)/i', $href) === 1) {
                $node->setAttribute('href', $href);
                if (preg_match('/^https?:\/\//i', $href) === 1) {
                    $node->setAttribute('rel', 'noopener noreferrer');
                }
            }
            if ($tag === 'img' && preg_match('/^(?:https?:\/\/|\/media\/)/i', $src) === 1) {
                $node->setAttribute('src', $src);
                $node->setAttribute('alt', mb_substr(trim(strip_tags($alt)), 0, 255));
                $node->setAttribute('loading', 'lazy');
            }
            if ($tag === 'img' && ! $node->hasAttribute('src')) {
                $parent->removeChild($node);

                continue;
            }
            if ($tag === 'figure') {
                $node->setAttribute('data-width', in_array($imageWidth, ['25', '50', '75', '100'], true) ? $imageWidth : '100');
                $node->setAttribute('data-align', in_array($imageAlign, ['left', 'center', 'right'], true) ? $imageAlign : 'center');
            }
            $this->clean($node);
        }
    }
}
