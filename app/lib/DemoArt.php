<?php
declare(strict_types=1);

/** Generates stylish SVG placeholder photos for demo content (studio + car silhouette). */
final class DemoArt
{
    public static function car(string $color, string $type = 'sedan', int $variant = 0): string
    {
        $suv = in_array($type, ['suv', 'pickup', 'crossover'], true);
        $body = $suv
            ? 'M110,335 C108,300 125,282 165,276 L250,262 C280,232 318,196 372,190 L560,188 C600,190 630,214 650,250 L700,262 C722,268 734,292 732,335 L728,352 L114,352 Z'
            : 'M112,338 C112,305 132,290 172,284 L268,272 C306,240 346,206 404,200 L520,198 C566,200 604,232 636,266 L694,278 C716,284 726,304 724,336 L720,350 L118,350 Z';
        $win = $suv
            ? '<path d="M270,262 C300,232 330,206 376,202 L452,202 L452,262 Z" /><path d="M464,202 L556,201 C590,203 614,226 630,256 L464,258 Z" />'
            : '<path d="M290,272 C322,244 356,216 404,212 L456,212 L456,270 Z" /><path d="M468,212 L518,211 C552,214 584,240 606,268 L468,270 Z" />';
        $wy = $suv ? 352 : 350;
        $bgs = [['#eef1f6', '#cfd6e2'], ['#1f2633', '#0c1018'], ['#f3efe8', '#d9cfbf']];
        [$b1, $b2] = $bgs[$variant % 3];
        $dark = $variant % 3 === 1;
        $flip = $variant === 1 ? ' transform="translate(840,0) scale(-1,1)"' : '';
        $vb = $variant === 2 ? '60 130 520 325' : '0 0 840 500';
        $strips = '';
        for ($i = 0; $i < 9; $i++) {
            $x = 40 + $i * 95;
            $strips .= "<rect x=\"$x\" y=\"0\" width=\"10\" height=\"300\" rx=\"5\" fill=\"#fff\" opacity=\"" . ($dark ? '.55' : '.85') . "\"/>";
        }
        $wheel = fn($cx) => "<g><circle cx=\"$cx\" cy=\"$wy\" r=\"50\" fill=\"#15181d\"/><circle cx=\"$cx\" cy=\"$wy\" r=\"30\" fill=\"#9aa3ad\"/>"
            . "<circle cx=\"$cx\" cy=\"$wy\" r=\"9\" fill=\"#3b4149\"/>"
            . implode('', array_map(fn($a) => "<rect x=\"" . ($cx - 3) . "\" y=\"" . ($wy - 28) . "\" width=\"6\" height=\"20\" rx=\"3\" fill=\"#5c646e\" transform=\"rotate($a $cx $wy)\"/>", [0, 72, 144, 216, 288]))
            . '</g>';
        $floorOp = $dark ? '.35' : '.06';
        $ramp = '';
        for ($i = 0; $i < 12; $i++) { $y = 405 + $i * 8; $ramp .= "<rect x=\"150\" y=\"$y\" width=\"540\" height=\"4\" fill=\"#000\" opacity=\".35\"/>"; }
        $w1 = $suv ? 235 : 232;
        $w2 = $suv ? 612 : 606;

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="$vb" preserveAspectRatio="xMidYMid slice">
<defs>
 <linearGradient id="bg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="$b1"/><stop offset="1" stop-color="$b2"/></linearGradient>
 <linearGradient id="paint" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fff" stop-opacity=".35"/><stop offset=".35" stop-color="#fff" stop-opacity="0"/><stop offset="1" stop-color="#000" stop-opacity=".35"/></linearGradient>
 <linearGradient id="glass" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#9fb3c8"/><stop offset="1" stop-color="#1e293b"/></linearGradient>
 <radialGradient id="sh" cx=".5" cy=".5" r=".5"><stop offset="0" stop-color="#000" stop-opacity=".45"/><stop offset="1" stop-color="#000" stop-opacity="0"/></radialGradient>
</defs>
<rect width="840" height="500" fill="url(#bg)"/>
$strips
<rect y="300" width="840" height="200" fill="#000" opacity="$floorOp"/>
<g opacity=".5">$ramp</g>
<ellipse cx="420" cy="398" rx="330" ry="26" fill="url(#sh)"/>
<g$flip>
 <path d="$body" fill="$color"/>
 <path d="$body" fill="url(#paint)"/>
 <g fill="url(#glass)">$win</g>
 <path d="M462,212 L462,345" stroke="#000" stroke-opacity=".18" stroke-width="2"/>
 <path d="M300,300 L690,300" stroke="#fff" stroke-opacity=".25" stroke-width="2"/>
 <rect x="122" y="292" width="40" height="12" rx="6" fill="#fff8d6"/>
 <rect x="692" y="290" width="30" height="14" rx="6" fill="#d92d20"/>
 <rect x="480" y="306" width="26" height="5" rx="2.5" fill="#000" opacity=".3"/>
 <rect x="350" y="306" width="26" height="5" rx="2.5" fill="#000" opacity=".3"/>
 {$wheel($w1)}{$wheel($w2)}
</g>
</svg>
SVG;
    }

    public static function generate(string $color, string $type, int $variant): string
    {
        $svg = self::car($color, $type, $variant);
        return $svg;
    }

    public static function part(string $label, string $hue): string
    {
        $l = htmlspecialchars($label, ENT_XML1);
        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 400">
<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="$hue" stop-opacity=".18"/><stop offset="1" stop-color="$hue" stop-opacity=".45"/></linearGradient></defs>
<rect width="400" height="400" fill="#f8fafc"/><rect width="400" height="400" fill="url(#g)"/>
<circle cx="200" cy="175" r="88" fill="none" stroke="$hue" stroke-width="18" opacity=".85"/>
<circle cx="200" cy="175" r="34" fill="$hue" opacity=".85"/>
<g stroke="$hue" stroke-width="14" stroke-linecap="round" opacity=".85">
<line x1="200" y1="62" x2="200" y2="92"/><line x1="200" y1="258" x2="200" y2="288"/><line x1="87" y1="175" x2="117" y2="175"/><line x1="283" y1="175" x2="313" y2="175"/>
</g>
<text x="200" y="345" text-anchor="middle" font-family="Tahoma, sans-serif" font-size="26" fill="#1f2937">$l</text>
</svg>
SVG;
    }
}
