<?php
/**
 * Exploded car body diagram (Khodro45 style):
 *   front bumper (top) · left side unfolded · top view (hood/roof/trunk) · right side unfolded · rear bumper (bottom)
 * $damage = [part => status]   whole-panel status (tints the panel)
 * $points = [[id, part, type, x, y, note, photo, thumb], ...]   spot markers in viewBox units (600 x 780)
 * $admin  = bool  editor mode
 */
$damage = $damage ?? [];
$points = $points ?? [];
$admin = $admin ?? false;

// a panel is tinted by the worse of its panel status and its spot markers
$worst = $damage;
foreach ($points as $p) {
    if (Catalog::damageRank($p['type']) > Catalog::damageRank($worst[$p['part']] ?? 'ok')) $worst[$p['part']] = $p['type'];
}
$fill = fn(string $part) => ($s = $worst[$part] ?? 'ok') === 'ok' ? 'var(--dm-ok)' : 'color-mix(in oklab, ' . (Catalog::DAMAGE[$s][1] ?? '#999') . ' 38%, var(--dm-ok))';

// left side (car seen from its left, rotated so the front points up; wheels on the outer edge)
$L = [
    'fender_fl' => 'M98,112 Q122,100 152,104 L178,112 L178,240 L86,240 L86,152 Q86,126 98,112 Z',
    'door_fl'   => 'M86,246 L178,246 L178,380 L86,380 Z',
    'door_rl'   => 'M86,386 L178,386 L178,502 L86,502 Z',
    'fender_rl' => 'M86,508 L178,508 L178,626 L152,636 Q120,640 100,628 Q86,616 86,592 Z',
    'pillar_l'  => 'M204,236 L214,242 L214,508 L204,514 Z',
];
$glassL = ['M184,250 L198,262 L198,376 L184,376 Z', 'M184,390 L198,390 L198,490 L184,502 Z'];
$mx = fn(string $d) => preg_replace_callback('/(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/', fn($m) => (600 - (float)$m[1]) . ',' . $m[2], $d);

$parts = [
    'front_bumper' => 'M212,42 Q300,22 388,42 L396,88 Q300,102 204,88 Z',
    'hood'         => 'M236,118 Q300,104 364,118 L374,252 Q300,242 226,252 Z',
    'roof'         => 'M244,312 Q300,306 356,312 L356,470 Q300,476 244,470 Z',
    'trunk'        => 'M226,520 Q300,530 374,520 L364,616 Q300,630 236,616 Z',
    'rear_bumper'  => 'M204,690 Q300,676 396,690 L388,740 Q300,758 212,740 Z',
] + $L;
foreach ($L as $k => $d) $parts[strtr($k, ['_fl' => '_fr', '_rl' => '_rr', '_l' => '_r'])] = $mx($d);

$wheel = function (float $cx, float $cy): string {
    $s = '<g class="wheel" transform="translate(' . $cx . ' ' . $cy . ')"><circle class="tire" r="31"/><circle class="rim" r="19"/>';
    for ($a = 0; $a < 360; $a += 72) {
        $r = deg2rad($a);
        $s .= '<line x1="0" y1="0" x2="' . round(17 * cos($r), 1) . '" y2="' . round(17 * sin($r), 1) . '"/>';
    }
    return $s . '<circle class="hub" r="5"/></g>';
};
?>
<svg class="damage-svg<?= $admin ? ' damage-admin' : '' ?>" viewBox="0 0 600 780" role="img" aria-label="نقشه وضعیت بدنه خودرو" data-damage-svg>
  <defs>
    <filter id="dmgShadow" x="-20%" y="-20%" width="140%" height="140%"><feDropShadow dx="0" dy="2" stdDeviation="2.2" flood-opacity=".28"/></filter>
  </defs>
  <!-- labels -->
  <text class="lbl" x="300" y="16" text-anchor="middle">جلو</text>
  <text class="lbl" x="300" y="774" text-anchor="middle">عقب</text>
  <text class="lbl" x="34" y="372" text-anchor="middle" transform="rotate(-90 34 372)">سمت چپ</text>
  <text class="lbl" x="566" y="372" text-anchor="middle" transform="rotate(90 566 372)">سمت راست</text>

  <!-- top-view shell -->
  <path class="shell" d="M232,104 Q300,90 368,104 Q386,112 386,142 L386,604 Q386,630 360,636 Q300,648 240,636 Q214,630 214,604 L214,142 Q214,112 232,104 Z"/>
  <!-- glass -->
  <path class="glass" d="M228,258 Q300,248 372,258 L356,306 Q300,298 244,306 Z"/>
  <path class="glass" d="M244,476 Q300,482 356,476 L372,514 Q300,524 228,514 Z"/>
  <?php foreach ($glassL as $g): ?><path class="glass" d="<?= $g ?>"/><path class="glass" d="<?= $mx($g) ?>"/><?php endforeach ?>
  <!-- mirrors -->
  <path class="trim" d="M226,258 L208,250 Q200,260 208,270 L226,270 Z"/><path class="trim" d="<?= $mx('M226,258 L208,250 Q200,260 208,270 L226,270 Z') ?>"/>

  <!-- panels -->
  <?php foreach ($parts as $k => $d): ?>
    <path class="part<?= ($worst[$k] ?? 'ok') !== 'ok' ? ' has-dmg' : '' ?>" id="p-<?= $k ?>" data-part="<?= $k ?>" d="<?= $d ?>" fill="<?= $fill($k) ?>"><title><?= e(Catalog::PARTS[$k]) ?>: <?= e(Catalog::DAMAGE[$worst[$k] ?? 'ok'][0]) ?></title></path>
  <?php endforeach ?>

  <!-- bumper details -->
  <g class="deco">
    <path d="M222,48 L262,45 L258,62 L226,64 Z"/><path d="<?= $mx('M222,48 L262,45 L258,62 L226,64 Z') ?>"/>
    <rect x="272" y="52" width="56" height="16" rx="5"/><rect class="plate" x="276" y="74" width="48" height="11" rx="2"/>
    <path d="M218,694 L262,690 L262,704 L222,706 Z"/><path d="<?= $mx('M218,694 L262,690 L262,704 L222,706 Z') ?>"/>
    <rect class="plate" x="276" y="712" width="48" height="11" rx="2"/>
    <!-- door handles -->
    <rect x="160" y="300" width="4" height="18" rx="2"/><rect x="160" y="436" width="4" height="18" rx="2"/>
    <rect x="436" y="300" width="4" height="18" rx="2"/><rect x="436" y="436" width="4" height="18" rx="2"/>
  </g>

  <!-- wheels -->
  <?= $wheel(70, 178) . $wheel(70, 572) . $wheel(530, 178) . $wheel(530, 572) ?>

  <!-- spot markers -->
  <g class="pins" data-pins>
    <?php foreach ($points as $i => $p): $c = Catalog::DAMAGE[$p['type']][1] ?? '#999'; ?>
      <g class="pin<?= !empty($p['photo']) ? ' has-photo' : '' ?>" data-pin="<?= $i ?>" data-on="<?= e($p['part']) ?>" transform="translate(<?= (int)$p['x'] ?> <?= (int)$p['y'] ?>)" tabindex="0" role="button"
         aria-label="<?= e((Catalog::PARTS[$p['part']] ?? '') . ' — ' . (Catalog::DAMAGE[$p['type']][0] ?? '')) ?>">
        <circle class="pulse" r="13" fill="<?= $c ?>"/>
        <circle r="13" fill="<?= $c ?>" stroke="#fff" stroke-width="2.5" filter="url(#dmgShadow)"/>
        <?= Catalog::damageGlyph($p['type']) ?>
        <?php if (!empty($p['photo'])): ?><g class="cam" transform="translate(10 -10)"><circle r="6.5"/><rect x="-3.4" y="-2.2" width="6.8" height="4.6" rx="1"/><circle r="1.3" class="lens"/></g><?php endif ?>
      </g>
    <?php endforeach ?>
  </g>
</svg>
