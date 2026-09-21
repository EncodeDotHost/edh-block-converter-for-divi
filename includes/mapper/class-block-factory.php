<?php
/**
 * Core block builders.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg\Mapper;

/**
 * Builds parsed-block arrays for core blocks.
 *
 * Each builder writes the HTML that the `save` function of the block
 * writes. If the HTML is different, the editor shows "invalid block".
 * An attribute that the block reads from the HTML must not be in `attrs`.
 */
final class Block_Factory {

	/**
	 * Builds a block without inner blocks.
	 *
	 * @param string $name  Block name.
	 * @param array  $attrs Comment-delimiter attributes.
	 * @param string $html  Saved HTML. Empty for dynamic blocks.
	 * @return array
	 */
	public static function leaf( $name, array $attrs = array(), $html = '' ) {
		return array(
			'blockName'    => $name,
			'attrs'        => self::clean( $attrs ),
			'innerBlocks'  => array(),
			'innerHTML'    => $html,
			'innerContent' => '' === $html ? array() : array( $html ),
		);
	}

	/**
	 * Builds a block with inner blocks.
	 *
	 * @param string $name  Block name.
	 * @param array  $attrs Comment-delimiter attributes.
	 * @param array  $inner Inner blocks.
	 * @param string $open  HTML before the inner blocks.
	 * @param string $close HTML after the inner blocks.
	 * @return array
	 */
	public static function container( $name, array $attrs, array $inner, $open = '', $close = '' ) {
		$inner   = array_values( $inner );
		$content = array();

		if ( '' !== $open ) {
			$content[] = $open;
		}
		foreach ( $inner as $unused ) {
			$content[] = null;
		}
		if ( '' !== $close ) {
			$content[] = $close;
		}

		return array(
			'blockName'    => $name,
			'attrs'        => self::clean( $attrs ),
			'innerBlocks'  => $inner,
			'innerHTML'    => $open . $close,
			'innerContent' => $content,
		);
	}

	/**
	 * Builds a paragraph.
	 *
	 * @param string $html  Inline HTML.
	 * @param array  $attrs Attributes: `align`, `style`, `anchor`, `className`.
	 * @return array
	 */
	public static function paragraph( $html, array $attrs = array() ) {
		$classes = array();
		if ( ! empty( $attrs['align'] ) ) {
			$classes[] = 'has-text-align-' . $attrs['align'];
		}
		return self::leaf( 'core/paragraph', $attrs, '<p' . self::props( '', $attrs, $classes ) . '>' . $html . '</p>' );
	}

	/**
	 * Builds a heading.
	 *
	 * @param string $html  Inline HTML.
	 * @param int    $level Level 1 to 6.
	 * @param array  $attrs Attributes: `textAlign`, `style`, `anchor`, `className`.
	 * @return array
	 */
	public static function heading( $html, $level = 2, array $attrs = array() ) {
		$level   = max( 1, min( 6, (int) $level ) );
		$classes = array();
		if ( ! empty( $attrs['textAlign'] ) ) {
			$classes[] = 'has-text-align-' . $attrs['textAlign'];
		}
		if ( 2 !== $level ) {
			$attrs['level'] = $level;
		}
		$tag = 'h' . $level;
		return self::leaf( 'core/heading', $attrs, '<' . $tag . self::props( 'wp-block-heading', $attrs, $classes ) . '>' . $html . '</' . $tag . '>' );
	}

	/**
	 * Builds a group.
	 *
	 * @param array $inner Inner blocks.
	 * @param array $attrs Attributes.
	 * @return array
	 */
	public static function group( array $inner, array $attrs = array() ) {
		return self::container( 'core/group', $attrs, $inner, '<div' . self::props( 'wp-block-group', $attrs ) . '>', '</div>' );
	}

	/**
	 * Builds a columns block.
	 *
	 * @param array $columns Column blocks.
	 * @param array $attrs   Attributes.
	 * @return array
	 */
	public static function columns( array $columns, array $attrs = array() ) {
		return self::container( 'core/columns', $attrs, $columns, '<div' . self::props( 'wp-block-columns', $attrs ) . '>', '</div>' );
	}

	/**
	 * Builds one column.
	 *
	 * @param array $inner Inner blocks.
	 * @param array $attrs Attributes: `width` as a percent string.
	 * @return array
	 */
	public static function column( array $inner, array $attrs = array() ) {
		$styles = array();
		if ( ! empty( $attrs['width'] ) ) {
			$styles[] = 'flex-basis:' . $attrs['width'];
		}
		return self::container( 'core/column', $attrs, $inner, '<div' . self::props( 'wp-block-column', $attrs, array(), $styles ) . '>', '</div>' );
	}

	/**
	 * Builds an image.
	 *
	 * @param array $image Keys: `url`, `alt`, `title`, `href`, `target`, `caption`.
	 * @param array $attrs Attributes: `id`, `sizeSlug`, `align`, `lightbox`, `anchor`, `className`.
	 * @return array
	 */
	public static function image( array $image, array $attrs = array() ) {
		$image = array_merge(
			array(
				'url'     => '',
				'alt'     => '',
				'title'   => '',
				'href'    => '',
				'target'  => '',
				'caption' => '',
			),
			$image
		);

		$classes = array();
		if ( ! empty( $attrs['sizeSlug'] ) ) {
			$classes[] = 'size-' . $attrs['sizeSlug'];
		}

		$img = '<img src="' . esc_url( $image['url'] ) . '" alt="' . esc_attr( $image['alt'] ) . '"';
		if ( ! empty( $attrs['id'] ) ) {
			$img .= ' class="wp-image-' . (int) $attrs['id'] . '"';
		}
		if ( '' !== $image['title'] ) {
			$img .= ' title="' . esc_attr( $image['title'] ) . '"';
		}
		$img .= '/>';

		if ( '' !== $image['href'] ) {
			$attrs['linkDestination'] = 'custom';

			$link = '<a href="' . esc_url( $image['href'] ) . '"';
			if ( '_blank' === $image['target'] ) {
				$link .= ' target="_blank" rel="noreferrer noopener"';
			}
			$img = $link . '>' . $img . '</a>';
		}

		if ( '' !== $image['caption'] ) {
			$img .= '<figcaption class="wp-element-caption">' . $image['caption'] . '</figcaption>';
		}

		$attrs = self::without_style( $attrs );

		return self::leaf( 'core/image', $attrs, '<figure' . self::props( 'wp-block-image', $attrs, $classes ) . '>' . $img . '</figure>' );
	}

	/**
	 * Builds a buttons block.
	 *
	 * @param array  $buttons Button blocks.
	 * @param string $justify `left`, `center`, `right`, or empty.
	 * @param array  $attrs   Attributes.
	 * @return array
	 */
	public static function buttons( array $buttons, $justify = '', array $attrs = array() ) {
		if ( '' !== $justify ) {
			$attrs['layout'] = array(
				'type'           => 'flex',
				'justifyContent' => $justify,
			);
		}
		$attrs = self::without_style( $attrs );
		return self::container( 'core/buttons', $attrs, $buttons, '<div' . self::props( 'wp-block-buttons', $attrs ) . '>', '</div>' );
	}

	/**
	 * Builds one button.
	 *
	 * The button block writes the colours and the radius on the link element.
	 *
	 * @param string $text   Inline HTML of the label.
	 * @param string $url    Link.
	 * @param bool   $new_tab True to open the link in a new tab.
	 * @param array  $colors Keys: `text`, `background`, `radius`.
	 * @return array
	 */
	public static function button( $text, $url = '', $new_tab = false, array $colors = array() ) {
		$classes = array( 'wp-block-button__link' );
		$styles  = array();
		$style   = array();

		if ( ! empty( $colors['radius'] ) ) {
			$style['border']['radius'] = $colors['radius'];
			$styles[]                  = 'border-radius:' . $colors['radius'];
		}
		if ( ! empty( $colors['text'] ) ) {
			$style['color']['text'] = $colors['text'];
			$classes[]              = 'has-text-color';
			$styles[]               = 'color:' . $colors['text'];
		}
		if ( ! empty( $colors['background'] ) ) {
			$style['color']['background'] = $colors['background'];
			$classes[]                    = 'has-background';
			$styles[]                     = 'background-color:' . $colors['background'];
		}
		$classes[] = 'wp-element-button';

		$link = '<a class="' . esc_attr( implode( ' ', $classes ) ) . '"';
		if ( '' !== $url ) {
			$link .= ' href="' . esc_url( $url ) . '"';
			if ( $new_tab ) {
				$link .= ' target="_blank" rel="noreferrer noopener"';
			}
		}
		if ( $styles ) {
			$link .= ' style="' . esc_attr( implode( ';', $styles ) ) . '"';
		}
		$link .= '>' . $text . '</a>';

		return self::leaf( 'core/button', array( 'style' => $style ), '<div class="wp-block-button">' . $link . '</div>' );
	}

	/**
	 * Builds a separator.
	 *
	 * @param string $color Optional colour.
	 * @param array  $attrs Attributes.
	 * @return array
	 */
	public static function separator( $color = '', array $attrs = array() ) {
		$attrs   = self::without_style( $attrs );
		$classes = array( 'has-alpha-channel-opacity' );
		$styles  = array();

		if ( '' !== $color ) {
			$attrs['style'] = array( 'color' => array( 'background' => $color ) );
			$classes        = array( 'has-text-color', 'has-alpha-channel-opacity', 'has-background' );
			$styles         = array( 'background-color:' . $color, 'color:' . $color );
		}

		return self::leaf( 'core/separator', $attrs, '<hr' . self::props( 'wp-block-separator', self::without_style( $attrs ), $classes, $styles ) . '/>' );
	}

	/**
	 * Builds a spacer.
	 *
	 * @param string $height CSS length.
	 * @return array
	 */
	public static function spacer( $height ) {
		return self::leaf(
			'core/spacer',
			array( 'height' => $height ),
			'<div style="height:' . esc_attr( $height ) . '" aria-hidden="true" class="wp-block-spacer"></div>'
		);
	}

	/**
	 * Builds a custom HTML block.
	 *
	 * @param string $html HTML.
	 * @return array
	 */
	public static function html( $html ) {
		return self::leaf( 'core/html', array(), $html );
	}

	/**
	 * Builds a shortcode block.
	 *
	 * @param string $shortcode Shortcode text.
	 * @return array
	 */
	public static function shortcode( $shortcode ) {
		return self::leaf( 'core/shortcode', array(), $shortcode );
	}

	/**
	 * Builds a quote.
	 *
	 * @param array  $inner    Inner blocks.
	 * @param string $citation Inline HTML of the citation.
	 * @param array  $attrs    Attributes.
	 * @return array
	 */
	public static function quote( array $inner, $citation = '', array $attrs = array() ) {
		$close = ( '' !== $citation ? '<cite>' . $citation . '</cite>' : '' ) . '</blockquote>';
		return self::container( 'core/quote', $attrs, $inner, '<blockquote' . self::props( 'wp-block-quote', $attrs ) . '>', $close );
	}

	/**
	 * Builds a details block.
	 *
	 * @param string $summary Inline HTML of the summary.
	 * @param array  $inner   Inner blocks.
	 * @param bool   $open    True to show the content initially.
	 * @param array  $attrs   Attributes.
	 * @return array
	 */
	public static function details( $summary, array $inner, $open = false, array $attrs = array() ) {
		if ( $open ) {
			$attrs['showContent'] = true;
		}
		$tag = '<details' . self::props( 'wp-block-details', $attrs ) . ( $open ? ' open' : '' ) . '>';
		return self::container( 'core/details', $attrs, $inner, $tag . '<summary>' . $summary . '</summary>', '</details>' );
	}

	/**
	 * Builds a list.
	 *
	 * @param array $items   List item blocks.
	 * @param bool  $ordered True for an ordered list.
	 * @param array $attrs   Attributes.
	 * @return array
	 */
	public static function list_block( array $items, $ordered = false, array $attrs = array() ) {
		$tag = $ordered ? 'ol' : 'ul';
		if ( $ordered ) {
			$attrs['ordered'] = true;
		}
		return self::container( 'core/list', $attrs, $items, '<' . $tag . self::props( 'wp-block-list', $attrs ) . '>', '</' . $tag . '>' );
	}

	/**
	 * Builds a list item.
	 *
	 * @param string $html   Inline HTML.
	 * @param array  $nested Nested list blocks.
	 * @return array
	 */
	public static function list_item( $html, array $nested = array() ) {
		if ( ! $nested ) {
			return self::leaf( 'core/list-item', array(), '<li>' . $html . '</li>' );
		}
		return self::container( 'core/list-item', array(), $nested, '<li>' . $html, '</li>' );
	}

	/**
	 * Builds a cover with a background image.
	 *
	 * @param array $inner Inner blocks.
	 * @param array $cover Keys: `url`, `id`, `alt`, `parallax`, `overlay`, `dim`.
	 * @param array $attrs Attributes: `align`, `style` (padding only), `anchor`, `className`.
	 * @return array
	 */
	public static function cover( array $inner, array $cover, array $attrs = array() ) {
		$cover = array_merge(
			array(
				'url'      => '',
				'id'       => 0,
				'alt'      => '',
				'parallax' => false,
				'overlay'  => '',
				'dim'      => 0,
			),
			$cover
		);

		$dim = (int) $cover['dim'];

		$attrs['url']      = $cover['url'];
		$attrs['dimRatio'] = $dim;
		$attrs['isDark']   = $dim >= 50;
		if ( $cover['id'] ) {
			$attrs['id'] = (int) $cover['id'];
		}
		if ( '' !== $cover['alt'] ) {
			$attrs['alt'] = $cover['alt'];
		}
		if ( $cover['parallax'] ) {
			$attrs['hasParallax'] = true;
		}
		if ( '' !== $cover['overlay'] ) {
			$attrs['customOverlayColor'] = $cover['overlay'];
		}

		// The cover writes only the spacing styles on its wrapper.
		if ( isset( $attrs['style'] ) ) {
			$attrs['style'] = array_intersect_key( $attrs['style'], array( 'spacing' => true ) );
		}

		$classes = array();
		if ( ! $attrs['isDark'] ) {
			$classes[] = 'is-light';
		}
		if ( $cover['parallax'] ) {
			$classes[] = 'has-parallax';
		}

		$media_classes = 'wp-block-cover__image-background' . ( $cover['id'] ? ' wp-image-' . (int) $cover['id'] : '' );
		if ( $cover['parallax'] ) {
			$media  = '<div';
			$media .= '' !== $cover['alt'] ? ' role="img" aria-label="' . esc_attr( $cover['alt'] ) . '"' : '';
			$media .= ' class="' . esc_attr( $media_classes . ' has-parallax' ) . '"';
			$media .= ' style="background-position:50% 50%;background-image:url(' . esc_url( $cover['url'] ) . ')"></div>';
		} else {
			$media = '<img class="' . esc_attr( $media_classes ) . '" alt="' . esc_attr( $cover['alt'] ) . '" src="' . esc_url( $cover['url'] ) . '" data-object-fit="cover"/>';
		}

		$span_classes = 'wp-block-cover__background';
		if ( 50 !== $dim ) {
			$span_classes .= ' has-background-dim-' . ( 10 * (int) round( $dim / 10 ) );
		}
		$span_classes .= ' has-background-dim';

		$span = '<span aria-hidden="true" class="' . esc_attr( $span_classes ) . '"';
		if ( '' !== $cover['overlay'] ) {
			$span .= ' style="background-color:' . esc_attr( $cover['overlay'] ) . '"';
		}
		$span .= '></span>';

		$open = '<div' . self::props( 'wp-block-cover', $attrs, $classes ) . '>' . $media . $span . '<div class="wp-block-cover__inner-container">';

		return self::container( 'core/cover', $attrs, $inner, $open, '</div></div>' );
	}

	/**
	 * Builds a gallery.
	 *
	 * @param array $images Image blocks.
	 * @param array $attrs  Attributes.
	 * @return array
	 */
	public static function gallery( array $images, array $attrs = array() ) {
		$attrs['linkTo'] = 'none';
		$attrs           = self::without_style( $attrs );
		$classes         = array( 'has-nested-images', 'columns-default', 'is-cropped' );
		if ( ! empty( $attrs['columns'] ) ) {
			$classes[1] = 'columns-' . (int) $attrs['columns'];
		}
		return self::container( 'core/gallery', $attrs, $images, '<figure' . self::props( 'wp-block-gallery', $attrs, $classes ) . '>', '</figure>' );
	}

	/**
	 * Builds a video.
	 *
	 * @param string $src    Video URL.
	 * @param string $poster Poster image URL.
	 * @return array
	 */
	public static function video( $src, $poster = '' ) {
		$video = '<video controls';
		if ( '' !== $poster ) {
			$video .= ' poster="' . esc_url( $poster ) . '"';
		}
		$video .= ' src="' . esc_url( $src ) . '"></video>';
		return self::leaf( 'core/video', array(), '<figure class="wp-block-video">' . $video . '</figure>' );
	}

	/**
	 * Builds an audio block.
	 *
	 * @param string $src Audio URL.
	 * @return array
	 */
	public static function audio( $src ) {
		return self::leaf( 'core/audio', array(), '<figure class="wp-block-audio"><audio controls src="' . esc_url( $src ) . '"></audio></figure>' );
	}

	/**
	 * Builds an embed.
	 *
	 * @param string $url      URL.
	 * @param string $provider Provider slug, for example `youtube`.
	 * @param string $type     Embed type, for example `video`.
	 * @return array
	 */
	public static function embed( $url, $provider = '', $type = 'rich' ) {
		$attrs   = array(
			'url'  => $url,
			'type' => $type,
		);
		$classes = 'wp-block-embed is-type-' . $type;
		if ( '' !== $provider ) {
			$attrs['providerNameSlug'] = $provider;
			$classes                  .= ' is-provider-' . $provider . ' wp-block-embed-' . $provider;
		}
		$html = '<figure class="' . esc_attr( $classes ) . '"><div class="wp-block-embed__wrapper">' . "\n" . esc_url( $url ) . "\n" . '</div></figure>';
		return self::leaf( 'core/embed', $attrs, $html );
	}

	/**
	 * Builds a dynamic block. WordPress renders it on the server.
	 *
	 * @param string $name  Block name.
	 * @param array  $attrs Attributes.
	 * @param array  $inner Inner blocks.
	 * @return array
	 */
	public static function dynamic( $name, array $attrs = array(), array $inner = array() ) {
		return $inner ? self::container( $name, $attrs, $inner ) : self::leaf( $name, $attrs );
	}

	/**
	 * Builds the wrapper attributes that the block supports write.
	 *
	 * @param string   $base    Base class of the block. Empty for none.
	 * @param array    $attrs   Block attributes.
	 * @param string[] $classes More classes.
	 * @param string[] $styles  More style declarations.
	 * @return string
	 */
	private static function props( $base, array $attrs, array $classes = array(), array $styles = array() ) {
		$all_classes = '' === $base ? array() : array( $base );
		$all_classes = array_merge( $all_classes, $classes );
		$style       = isset( $attrs['style'] ) && is_array( $attrs['style'] ) ? $attrs['style'] : array();

		// In a paragraph, `align` is the text alignment. The caller adds that class.
		$block_aligns = 'wp-block-image' === $base ? array( 'left', 'center', 'right', 'wide', 'full' ) : array( 'wide', 'full' );
		if ( ! empty( $attrs['align'] ) && '' !== $base && in_array( $attrs['align'], $block_aligns, true ) ) {
			$all_classes[] = 'align' . $attrs['align'];
		}

		if ( ! empty( $style['color']['text'] ) ) {
			$all_classes[] = 'has-text-color';
			$styles[]      = 'color:' . $style['color']['text'];
		}
		if ( ! empty( $style['color']['gradient'] ) ) {
			$all_classes[] = 'has-background';
			$styles[]      = 'background:' . $style['color']['gradient'];
		} elseif ( ! empty( $style['color']['background'] ) ) {
			$all_classes[] = 'has-background';
			$styles[]      = 'background-color:' . $style['color']['background'];
		}

		foreach ( array( 'margin', 'padding' ) as $property ) {
			if ( empty( $style['spacing'][ $property ] ) ) {
				continue;
			}
			foreach ( $style['spacing'][ $property ] as $side => $value ) {
				$styles[] = $property . '-' . $side . ':' . $value;
			}
		}

		if ( ! empty( $style['typography']['fontSize'] ) ) {
			$styles[] = 'font-size:' . $style['typography']['fontSize'];
		}

		if ( ! empty( $attrs['className'] ) ) {
			$all_classes[] = $attrs['className'];
		}

		$html = '';
		if ( ! empty( $attrs['anchor'] ) ) {
			$html .= ' id="' . esc_attr( $attrs['anchor'] ) . '"';
		}
		if ( $all_classes ) {
			$html .= ' class="' . esc_attr( implode( ' ', $all_classes ) ) . '"';
		}
		if ( $styles ) {
			$html .= ' style="' . esc_attr( implode( ';', $styles ) ) . '"';
		}

		return $html;
	}

	/**
	 * Removes the style attribute for a block that does not write it.
	 *
	 * @param array $attrs Attributes.
	 * @return array
	 */
	private static function without_style( array $attrs ) {
		unset( $attrs['style'] );
		return $attrs;
	}

	/**
	 * Removes empty attributes. Keeps `false` and `0`.
	 *
	 * @param array $attrs Attributes.
	 * @return array
	 */
	private static function clean( array $attrs ) {
		foreach ( $attrs as $key => $value ) {
			if ( null === $value || '' === $value || array() === $value ) {
				unset( $attrs[ $key ] );
			}
		}
		return $attrs;
	}
}
