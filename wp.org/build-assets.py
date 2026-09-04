#!/usr/bin/env python3
"""
Generates the wordpress.org artwork for B All-in-One AI Providers.

Follows the bPlugins house style used by b-media-fields-for-cf7:
  - brand tile: #146EF5 -> #0B3FA8 gradient, square corners, no frame
  - accents: #FF7A00 orange, #070127 navy, white elements on the tile
  - banner: light gradient, tile with drop shadow on the left,
    Space Grotesk headline on the right, dark bPlugins logo bottom-right
  - icons carry a subtle CSS loop, frozen under prefers-reduced-motion,
    and their rest state equals the static PNG derivative.

Outputs (run from this folder):  python3 build-assets.py
  icon-aiswitch.svg / icon-aiswitch-animated.svg     (256x256)
  banner-aiswitch.svg / banner-aiswitch-animated.svg (1544x500)
"""
import base64
import os

HERE = os.path.dirname(os.path.abspath(__file__))
FONTS = os.path.join(HERE, 'src-fonts')
LOGO = os.path.join(HERE, 'src-logo-dark.svg')

b64 = lambda p: base64.b64encode(open(p, 'rb').read()).decode()
BOLD = b64(os.path.join(FONTS, 'space-grotesk-bold.ttf'))
MEDIUM = b64(os.path.join(FONTS, 'space-grotesk-medium.ttf'))
LOGO_B64 = b64(LOGO)

BLUE, BLUE_D, ORANGE, NAVY, SUB = '#146EF5', '#0B3FA8', '#FF7A00', '#070127', '#485781'

REDUCED = '@media (prefers-reduced-motion: reduce) { * { animation: none !important; } }'

# --------------------------------------------------------------------------
# Icon artwork. Each entry: (slug, comment, <style>, body drawn in a 256 box)
# The body is reused unchanged inside the banner tile (scaled 1.25).
# --------------------------------------------------------------------------

# Four-point AI "sparkle" glyph.
def _spark(cx, cy, r, fill, cls):
    # Four-point star with a pinched waist: straight edges keep the square-corner feel.
    w = round(r * 0.22, 1)
    d = (f'M{cx} {cy-r} L{cx+w} {cy-w} L{cx+r} {cy} L{cx+w} {cy+w} '
         f'L{cx} {cy+r} L{cx-w} {cy+w} L{cx-r} {cy} L{cx-w} {cy-w} Z')
    return f'<path class="{cls}" d="{d}" fill="{fill}"/>'

# 6) AISWITCH: the AI sparkle above two provider rows with toggles (switch + spark).
AISWITCH_STYLE = f'''
      svg {{ --dur: 4.4s; }}
      .spark {{ transform-box: fill-box; transform-origin: 50% 50%; animation: breathe var(--dur) ease-in-out infinite; }}
      .spark-sm {{ animation-delay: calc(var(--dur) * -0.5); }}
      .knob {{ animation: slide var(--dur) ease-in-out infinite; }}
      .track {{ animation: tint var(--dur) ease-in-out infinite; }}
      @keyframes breathe {{ 0%, 100% {{ transform: scale(1); }} 30% {{ transform: scale(1.08); }} 60% {{ transform: scale(0.96); }} }}
      @keyframes slide {{ 0%, 55%, 100% {{ transform: translateX(0); }} 65%, 90% {{ transform: translateX(-20px); }} }}
      @keyframes tint  {{ 0%, 55%, 100% {{ fill: {ORANGE}; }} 65%, 90% {{ fill: #9fb8e8; }} }}
      {REDUCED}'''
def _row_short(y, logo_fill, on, animate=False):
    knob_x = 196 if on else 176
    cls_t = ' class="track"' if animate else ''
    cls_k = ' class="knob"' if animate else ''
    return f'''
      <rect x="32" y="{y}" width="192" height="40" fill="#ffffff"/>
      <rect x="42" y="{y+9}" width="22" height="22" fill="{logo_fill}"/>
      <rect x="76" y="{y+15}" width="60" height="10" fill="{BLUE_D}" fill-opacity="0.35"/>
      <rect{cls_t} x="172" y="{y+8}" width="44" height="24" fill="{ORANGE if on else "#9fb8e8"}"/>
      <rect{cls_k} x="{knob_x}" y="{y+12}" width="16" height="16" fill="#ffffff"/>'''
AISWITCH_BODY = f'''
      <!-- AI sparkles -->
      {_spark(112, 84, 56, "#ffffff", "spark")}
      {_spark(184, 48, 22, ORANGE, "spark spark-sm")}
      <!-- provider rows -->{_row_short(150, BLUE, True, animate=True)}{_row_short(200, NAVY, False)}'''

ICONS = [
    ('aiswitch', 'AI switch: the sparkle above provider rows with toggles.', AISWITCH_STYLE, AISWITCH_BODY),
]

def icon_svg(slug, comment, style, body):
    return f'''<svg xmlns="http://www.w3.org/2000/svg" width="256" height="256" viewBox="0 0 256 256">
  <!--
    B All-in-One AI Providers — plugin icon, variant "{slug}".
    {comment}
    bPlugins brand: {BLUE} blue, {ORANGE} orange, {NAVY} navy.
    Square corners throughout; no card frame — elements sit on the brand tile.
    Rest state (0% / 100%) is identical to the static PNG derivatives.
  -->
  <defs>
    <style>{style}
    </style>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="{BLUE}"/>
      <stop offset="1" stop-color="{BLUE_D}"/>
    </linearGradient>
  </defs>

  <rect width="256" height="256" fill="url(#bg)"/>
{body}
</svg>
'''

# --------------------------------------------------------------------------
# Banners. (slug, pill, hero line 1, hero line 2, subline, hero size)
# --------------------------------------------------------------------------
BANNERS = [
    ('aiswitch', 'ALL-IN-ONE AI PROVIDERS', 'Connect any AI', 'to WordPress.', 'Enable, test and prioritise 11 providers from one settings screen'),
]

def banner_svg(slug, pill, hero1, hero2, sub, body):
    pill_w = int(len(pill) * 16.4 + 20)          # matches the reference metric
    pill_cx = 560 + pill_w / 2
    return f'''<svg xmlns="http://www.w3.org/2000/svg" width="1544" height="500" viewBox="0 0 1544 500">
  <!--
    B All-in-One AI Providers — wordpress.org banner (1544x500), variant "{slug}".
    bPlugins brand: Space Grotesk, {BLUE} blue, {ORANGE} orange, {NAVY} navy.
  -->
  <defs>
    <style>
      @font-face {{
        font-family: "Space Grotesk";
        font-weight: 700;
        src: url(data:font/ttf;base64,{BOLD}) format("truetype");
      }}
      @font-face {{
        font-family: "Space Grotesk";
        font-weight: 500;
        src: url(data:font/ttf;base64,{MEDIUM}) format("truetype");
      }}
      .sg {{ font-family: "Space Grotesk", "Inter", system-ui, sans-serif; }}
    </style>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#FFFFFF"/>
      <stop offset="0.55" stop-color="#F2F7FF"/>
      <stop offset="1" stop-color="#E2ECFD"/>
    </linearGradient>
    <radialGradient id="glow" cx="0.5" cy="0.5" r="0.5">
      <stop offset="0" stop-color="{BLUE}" stop-opacity="0.14"/>
      <stop offset="1" stop-color="{BLUE}" stop-opacity="0"/>
    </radialGradient>
    <linearGradient id="tile" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="{BLUE}"/>
      <stop offset="1" stop-color="{BLUE_D}"/>
    </linearGradient>
    <filter id="shadow" x="-25%" y="-25%" width="150%" height="150%">
      <feDropShadow dx="0" dy="20" stdDeviation="22" flood-color="{BLUE_D}" flood-opacity="0.28"/>
    </filter>
  </defs>

  <rect width="1544" height="500" fill="url(#bg)"/>
  <circle cx="300" cy="250" r="300" fill="url(#glow)"/>

  <!-- decorative shapes -->
  <g fill="{BLUE}" fill-opacity="0.06">
    <circle cx="1400" cy="60" r="110"/>
    <circle cx="1470" cy="430" r="150"/>
  </g>
  <g fill="{ORANGE}" fill-opacity="0.07">
    <circle cx="1230" cy="450" r="60"/>
  </g>

  <!-- ICON ZONE -->
  <g transform="translate(140,90)" filter="url(#shadow)">
    <rect width="320" height="320" fill="url(#tile)"/>
    <g transform="scale(1.25)">{body}
    </g>
  </g>

  <!-- MESSAGE ZONE -->
  <g class="sg">
    <rect x="560" y="100" width="{pill_w}" height="46" fill="{BLUE}" fill-opacity="0.10"/>
    <text x="{pill_cx}" y="130" text-anchor="middle" font-size="20" font-weight="700" letter-spacing="3" fill="{BLUE_D}">{pill}</text>

    <text x="560" y="240" font-size="88" font-weight="700" fill="{NAVY}" letter-spacing="-2">{hero1}</text>
    <text x="560" y="328" font-size="88" font-weight="700" fill="{NAVY}" letter-spacing="-2">{hero2}</text>

    <rect x="562" y="366" width="120" height="6" fill="{ORANGE}"/>
    <text x="562" y="406" font-size="28" font-weight="500" fill="{SUB}">{sub.replace('&', '&amp;')}</text>
  </g>

  <!-- bPlugins logo (dark version for the light background) -->
  <image x="1294" y="418" width="210" height="42" href="data:image/svg+xml;base64,{LOGO_B64}"/>
</svg>
'''

if __name__ == '__main__':
    bodies = {}
    for slug, comment, style, body in ICONS:
        # Static body for the banner: strip animation classes so it renders at rest.
        bodies[slug] = body
        with open(os.path.join(HERE, f'icon-{slug}.svg'), 'w') as fh:
            fh.write(icon_svg(slug, comment, style, body))
        print('wrote icon-%s.svg' % slug)
    for slug, pill, h1, h2, sub in BANNERS:
        with open(os.path.join(HERE, f'banner-{slug}.svg'), 'w') as fh:
            fh.write(banner_svg(slug, pill, h1, h2, sub, bodies[slug]))
        print('wrote banner-%s.svg' % slug)

# --------------------------------------------------------------------------
# Animated pair for the "aiswitch" variant.
#   icon-aiswitch-animated.svg   - looping tile (sparkle breathes + twinkles,
#                                  toggle flips, rows glide in)
#   banner-aiswitch-animated.svg - same tile loop inside the banner plus a
#                                  one-shot entrance for pill / headline /
#                                  underline / subline. Frozen under
#                                  prefers-reduced-motion; final frame equals
#                                  the static banner.
# --------------------------------------------------------------------------
ANIM_TILE_STYLE = f'''
      .aisw {{ --dur: 4.4s; }}
      .aisw .spark {{ transform-box: fill-box; transform-origin: 50% 50%; animation: aisw-breathe var(--dur) ease-in-out infinite; }}
      .aisw .spark-sm {{ animation: aisw-twinkle var(--dur) ease-in-out infinite; animation-delay: calc(var(--dur) * -0.5); }}
      .aisw .knob {{ animation: aisw-slide var(--dur) ease-in-out infinite; }}
      .aisw .track {{ animation: aisw-tint var(--dur) ease-in-out infinite; }}
      @keyframes aisw-breathe {{ 0%, 100% {{ transform: scale(1) rotate(0deg); }} 30% {{ transform: scale(1.08) rotate(4deg); }} 60% {{ transform: scale(0.96) rotate(-3deg); }} }}
      @keyframes aisw-twinkle {{ 0%, 100% {{ transform: scale(1); opacity: 1; }} 25% {{ transform: scale(0.6); opacity: 0.4; }} 50% {{ transform: scale(1.15); opacity: 1; }} }}
      @keyframes aisw-slide {{ 0%, 55%, 100% {{ transform: translateX(0); }} 65%, 90% {{ transform: translateX(-20px); }} }}
      @keyframes aisw-tint  {{ 0%, 55%, 100% {{ fill: {ORANGE}; }} 65%, 90% {{ fill: #9fb8e8; }} }}
      {REDUCED}'''

ANIM_TEXT_STYLE = '''
      .in { opacity: 0; animation: aisw-in 0.7s cubic-bezier(.2,.7,.2,1) forwards; }
      .in-1 { animation-delay: 0.10s; } .in-2 { animation-delay: 0.30s; }
      .in-3 { animation-delay: 0.45s; } .in-4 { animation-delay: 0.70s; }
      .rule { transform-box: fill-box; transform-origin: 0 50%; transform: scaleX(0); animation: aisw-rule 0.6s cubic-bezier(.2,.7,.2,1) 0.6s forwards; }
      @keyframes aisw-in { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
      @keyframes aisw-rule { to { transform: scaleX(1); } }
      @media (prefers-reduced-motion: reduce) { .in, .rule { opacity: 1; transform: none; animation: none; } }'''

def animated_icon():
    return icon_svg('aiswitch-animated', 'AI switch, animated loop.', ANIM_TILE_STYLE,
                    f'\n      <g class="aisw">{AISWITCH_BODY}\n      </g>')

def animated_banner():
    slug, pill, h1, h2, sub = [b for b in BANNERS if b[0] == 'aiswitch'][0]
    svg = banner_svg('aiswitch-animated', pill, h1, h2, sub,
                     f'\n      <g class="aisw">{AISWITCH_BODY}\n      </g>')
    svg = svg.replace('.sg { font-family', ANIM_TILE_STYLE + ANIM_TEXT_STYLE + '\n      .sg { font-family', 1)
    # entrance classes on the message zone
    svg = svg.replace('<rect x="560" y="100"', '<rect class="in in-1" x="560" y="100"')
    svg = svg.replace('y="130" text-anchor="middle"', 'class="in in-1" y="130" text-anchor="middle"')
    svg = svg.replace('<text x="560" y="240"', '<text class="in in-2" x="560" y="240"')
    svg = svg.replace('<text x="560" y="328"', '<text class="in in-3" x="560" y="328"')
    svg = svg.replace('<rect x="562" y="366"', '<rect class="rule" x="562" y="366"')
    svg = svg.replace('<text x="562" y="406"', '<text class="in in-4" x="562" y="406"')
    return svg

if __name__ == '__main__':
    with open(os.path.join(HERE, 'icon-aiswitch-animated.svg'), 'w') as fh:
        fh.write(animated_icon())
    with open(os.path.join(HERE, 'banner-aiswitch-animated.svg'), 'w') as fh:
        fh.write(animated_banner())
    print('wrote icon-aiswitch-animated.svg, banner-aiswitch-animated.svg')
