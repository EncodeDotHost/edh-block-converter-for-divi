<?php
/**
 * Social follow mapper.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg\Mapper\Modules;

use EDH\DiviGutenberg\Context;
use EDH\DiviGutenberg\Mapper\Base_Mapper;
use EDH\DiviGutenberg\Mapper\Block_Factory;
use EDH\DiviGutenberg\Parser\Node;

/**
 * Converts `et_pb_social_media_follow` to a social links block.
 */
final class Social_Follow_Mapper extends Base_Mapper {

	/**
	 * Converts a node.
	 *
	 * @param Node    $node    Divi node.
	 * @param Context $context Context.
	 * @return array
	 */
	public function convert( Node $node, Context $context ) {
		$links = array();
		foreach ( $node->child_modules( array( 'et_pb_social_media_follow_network' ) ) as $network ) {
			$url = $network->attr( 'url' );
			if ( '' === $url || '#' === $url ) {
				continue;
			}
			$links[] = Block_Factory::dynamic(
				'core/social-link',
				array(
					'url'     => $url,
					'service' => self::service( $network->attr( 'social_network' ) ),
				)
			);
		}

		if ( ! $links ) {
			return array();
		}

		unset( $context );

		return array( Block_Factory::container( 'core/social-links', array(), $links, '<ul class="wp-block-social-links">', '</ul>' ) );
	}

	/**
	 * Gets the social link service for a Divi network name.
	 *
	 * @param string $network Divi network name.
	 * @return string
	 */
	public static function service( $network ) {
		$map = array(
			'google-plus' => 'google',
			'rss'         => 'feed',
			'myspace'     => 'chain',
			'flikr'       => 'flickr',
			'dribbble'    => 'dribbble',
		);
		if ( isset( $map[ $network ] ) ) {
			return $map[ $network ];
		}

		$known = array( 'amazon', 'bandcamp', 'behance', 'bluesky', 'deviantart', 'discord', 'dropbox', 'etsy', 'facebook', 'flickr', 'foursquare', 'github', 'goodreads', 'google', 'instagram', 'lastfm', 'linkedin', 'mastodon', 'medium', 'meetup', 'patreon', 'pinterest', 'reddit', 'skype', 'snapchat', 'soundcloud', 'spotify', 'telegram', 'threads', 'tiktok', 'tumblr', 'twitch', 'twitter', 'vimeo', 'vk', 'whatsapp', 'x', 'yelp', 'youtube' );

		return in_array( $network, $known, true ) ? $network : 'chain';
	}
}
