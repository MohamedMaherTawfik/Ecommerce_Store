<?php

namespace App\Security;

class HtmlSanitizer
{
    public function sanitize(string $html): string
    {
        if (! class_exists(\HTMLPurifier::class)) {
            return strip_tags($html, '<p><br><div><span><h1><h2><h3><h4><strong><b><em><i><u><ul><ol><li><a><blockquote><img><table><thead><tbody><tfoot><tr><th><td><hr><code><pre>');
        }

        $config = \HTMLPurifier_Config::createDefault();
        $config->set('Core.Encoding', 'UTF-8');
        $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
        $config->set(
            'HTML.Allowed',
            'p[style],br,div[style],span[style],h1[style],h2[style],h3[style],h4[style],strong,b,em,i,u,'
            .'ul,ol,li,a[href|title|target],blockquote,img[src|alt|title|width|height],'
            .'table[style],thead,tbody,tfoot,tr,th[style],td[style],hr,code,pre'
        );
        $config->set('CSS.AllowedProperties', [
            'color', 'background-color', 'font-size', 'font-family', 'font-weight',
            'text-align', 'line-height', 'padding', 'margin', 'border', 'width', 'max-width',
        ]);
        $config->set('URI.AllowedSchemes', [
            'http' => true,
            'https' => true,
            'mailto' => true,
            'cid' => true,
        ]);
        $config->set('HTML.SafeIframe', false);
        $config->set('Attr.AllowedFrameTargets', ['_blank']);

        return (new \HTMLPurifier($config))->purify($html);
    }
}
