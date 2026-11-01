import fs from 'node:fs';
import path from 'node:path';

const icons = [
  'layout-dashboard','shopping-cart','receipt-text','package','tags','boxes','users','chart-column',
  'settings','log-out','plus','search','x','menu','chevron-left','chevron-right','chevron-down','chevron-up',
  'panel-left-close','panel-left-open','store','user','lock','eye','eye-off','arrow-left','arrow-right',
  'trash-2','pencil','image','upload','check','alert-triangle','alert-circle','check-circle-2','info',
  'printer','download','file-text','calendar','filter','arrow-up','arrow-down','minus','banknote','wallet',
  'credit-card','calculator','shopping-bag','coffee','cookie','cup-soda','milk','glass-water','clock',
  'sparkles','trending-up','trending-down','circle-dollar-sign','package-check','package-x','barcode','hash',
  'phone','mail','map-pin','percentage','tag','layers','activity','history','badge-check','ban','key-round',
  'shield-check','external-link','arrow-up-right','more-horizontal','circle-check','circle-x','refresh-cw',
  'save','send','list-filter','sliders-horizontal','chef-hat','utensils','wheat','scan-line','user-round',
  'log-in','ellipsis','arrow-down-up','chevrons-up-down','layout-grid','panel-right-close','receipt','store',
];

const map = {};
for (const name of [...new Set(icons)]) {
  const file = path.join('node_modules', 'lucide-static', 'icons', `${name}.svg`);
  if (!fs.existsSync(file)) {
    console.warn('missing', name);
    continue;
  }
  const raw = fs.readFileSync(file, 'utf8');
  const inner = raw.match(/<svg[^>]*>([\s\S]*)<\/svg>/)?.[1]?.trim() ?? '';
  map[name] = inner.replace(/\s+'/g, "\\'");
}

let php = `<?php

namespace App\\Support;

/**
 * Inline Lucide icons, generated from lucide-static (ISC).
 * Regenerate with scripts/generate-icons.mjs rather than editing paths by hand.
 */
class Icons
{
    protected static array $icons = [
`;
for (const name of Object.keys(map).sort()) {
  php += `        '${name}' => '${map[name]}',\n`;
}
php += `    ];

    public static function get(string $name): string
    {
        return static::$icons[$name] ?? static::$icons['circle-check'];
    }

    public static function svg(string $name, string $class = 'h-5 w-5'): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="'.e($class).'" aria-hidden="true">'.static::get($name).'</svg>';
    }
}
`;
fs.mkdirSync('app/Support', { recursive: true });
fs.writeFileSync('app/Support/Icons.php', php);
fs.mkdirSync('scripts', { recursive: true });
console.log(`generated ${Object.keys(map).length} icons`);
