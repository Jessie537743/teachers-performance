<?php
/**
 * Ok, glad you are here
 * first we get a config instance, and set the settings
 * $config = HTMLPurifier_Config::createDefault();
 * $config->set('Core.Encoding', $this->config->get('purifier.encoding'));
 * $config->set('Cache.SerializerPath', $this->config->get('purifier.cachePath'));
 * if ( ! $this->config->get('purifier.finalize')) {
 *     $config->autoFinalize = false;
 * }
 * $config->loadArray($this->getConfig());
 *
 * You must NOT delete the default settings
 * anything in settings should be compacted with params that needed to instance HTMLPurifier_Config.
 *
 * @link http://htmlpurifier.org/live/configdoc/plain.html
 */

return [
    'encoding'           => 'UTF-8',
    'finalize'           => true,
    'ignoreNonStrings'   => false,
    'cachePath'          => storage_path('app/purifier'),
    'cacheFileMode'      => 0755,
    'settings'      => [
        'default' => [
            'HTML.Doctype'             => 'HTML 4.01 Transitional',
            'HTML.Allowed'             => 'div,b,strong,i,em,u,a[href|title],ul,ol,li,p[style],br,span[style],img[width|height|alt|src]',
            'CSS.AllowedProperties'    => 'font,font-size,font-weight,font-style,font-family,text-decoration,padding-left,color,background-color,text-align',
            'AutoFormat.AutoParagraph' => true,
            'AutoFormat.RemoveEmpty'   => true,
        ],
        'announcement' => [
            'HTML.Doctype'            => 'HTML 4.01 Transitional',
            'HTML.Allowed'            => 'p,br,strong,em,a[href|title|rel|target],ul,ol,li,blockquote,code,pre,h2,h3,h4,hr,table,thead,tbody,tr,th,td',
            'HTML.TargetBlank'        => true,
            'HTML.Nofollow'           => false,
            'Attr.AllowedFrameTargets'=> ['_blank'],
            'AutoFormat.AutoParagraph' => false,
            'AutoFormat.RemoveEmpty'  => true,
            'Core.EscapeInvalidTags'  => false,
            'URI.AllowedSchemes'      => [
                'http'   => true,
                'https'  => true,
                'mailto' => true,
            ],
        ],
    ],

];
