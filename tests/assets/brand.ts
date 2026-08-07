/**
 * The brand palette every listing asset is painted with.
 *
 * One module, imported by the icon rasteriser, the banner renderer and the
 * screenshot frames alike. Three copies of the same hex is exactly how an icon
 * and a banner drift apart.
 *
 * Every value is a Tailwind indigo stop, so the ramp is already
 * contrast-tested against itself rather than hand-picked to be close to
 * something. Indigo, which is as near PostgreSQL's own blue as an unaffiliated plugin should get.
 *
 * `.wordpress-org/icon.svg` repeats these values because SVG cannot import;
 * its header names this file as canonical.
 */
export const BRAND = {
	/** What the assets call the plugin. One place, so a rename is one edit. */
	name: 'PostgreSQL',
	nameAccent: 'for WordPress',
	tagline: 'WordPress on PostgreSQL',

	/** Ground, darkest first. */
	ink: '#1e1b4b',
	inkMid: '#312e81',
	inkLift: '#3730a3',

	/** Accent, deep to bright. */
	royal: '#4f46e5',
	royalLight: '#6366f1',
	sky: '#818cf8',

	/** The wordmark's gradient, and anything that must stay legible on the ground. */
	accent: '#a5b4fc',

	/** Shadow colour under white cards, so shadows read as the same hue. */
	shadow: '30, 27, 75',

	/** The glyph fill, top to bottom. */
	glyphTop: '#ffffff',
	glyphMid: '#eef2ff',
	glyphBase: '#c7d2fe',
} as const;

/**
 * The field, as stacked CSS backgrounds — glow, specular, ground, in the order
 * CSS paints them. `angle` tilts the ground ramp.
 *
 * @param angle Ground ramp angle, in degrees.
 */
export function field( angle = 135 ): string {
	return [
		'radial-gradient(70% 100% at 78% 104%, rgba(79, 70, 229, .38) 0%, rgba(79, 70, 229, 0) 64%)',
		'linear-gradient(to bottom, rgba(255, 255, 255, .10) 0%, rgba(255, 255, 255, 0) 34%)',
		`linear-gradient(${ angle }deg, ${ BRAND.ink } 0%, ${ BRAND.inkMid } 42%, ${ BRAND.inkLift } 100%)`,
	].join( ', ' );
}

/** Alias, so the banner and the frames can each call it by the name that fits. */
export const glassField = field;

/**
 * The mark, without its squircle — a tile inside a branded field would be a
 * panel on a panel.
 *
 * @param size Rendered size in pixels; the viewBox is always the 256 master.
 */
export function markSvg( size: number ): string {
	return `<svg width="${ size }" height="${ size }" viewBox="0 0 256 256" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
  <defs>
    <linearGradient id="mark-glyph" x1="50%" y1="0%" x2="50%" y2="100%">
      <stop offset="0" stop-color="${ BRAND.glyphTop }"/>
      <stop offset="0.6" stop-color="${ BRAND.glyphMid }"/>
      <stop offset="1" stop-color="${ BRAND.glyphBase }"/>
    </linearGradient>
  </defs>
	<ellipse cx="128" cy="66" rx="82" ry="30" fill="url(#mark-glyph)"/>
	<path d="M46 66 V190 a82 30 0 0 0 164 0 V66 a82 30 0 0 1 -164 0 Z" fill="url(#mark-glyph)" opacity="0.9"/>
	<ellipse cx="128" cy="128" rx="82" ry="30" fill="none" stroke="#1e1b4b" stroke-width="12" opacity="0.55"/>
	<ellipse cx="128" cy="190" rx="82" ry="30" fill="none" stroke="#1e1b4b" stroke-width="12" opacity="0.35"/>
</svg>`;
}
