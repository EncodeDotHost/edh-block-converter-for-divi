<?php
/**
 * Mapper registry.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg\Mapper;

/**
 * Finds the mapper for a Divi tag.
 */
final class Mapper_Registry {

	/**
	 * Map of tag to mapper.
	 *
	 * @var array<string,Mapper>
	 */
	private $mappers = array();

	/**
	 * Fallback mapper.
	 *
	 * @var Mapper
	 */
	private $fallback;

	/**
	 * Constructor.
	 *
	 * @param Mapper $fallback Mapper for tags without a registration.
	 */
	public function __construct( Mapper $fallback ) {
		$this->fallback = $fallback;
	}

	/**
	 * Builds the registry with all mappers of the plugin.
	 *
	 * @return Mapper_Registry
	 */
	public static function with_defaults() {
		$registry = new self( new Fallback_Mapper() );

		$row   = new Structure\Row_Mapper();
		$query = new Modules\Query_Mapper();
		$post  = new Modules\Post_Element_Mapper();
		$count = new Modules\Counter_Mapper();

		$map = array(
			'et_pb_section'                => new Structure\Section_Mapper( $row ),
			'et_pb_row'                    => $row,
			'et_pb_row_inner'              => $row,
			'et_pb_column'                 => new Structure\Column_Mapper(),
			'et_pb_column_inner'           => new Structure\Column_Mapper(),
			'et_pb_text'                   => new Modules\Text_Mapper(),
			'et_pb_heading'                => new Modules\Heading_Mapper(),
			'et_pb_image'                  => new Modules\Image_Mapper(),
			'et_pb_fullwidth_image'        => new Modules\Image_Mapper(),
			'et_pb_button'                 => new Modules\Button_Mapper(),
			'et_pb_divider'                => new Modules\Divider_Mapper(),
			'et_pb_code'                   => new Modules\Code_Mapper(),
			'et_pb_fullwidth_code'         => new Modules\Code_Mapper(),
			'et_pb_video'                  => new Modules\Video_Mapper(),
			'et_pb_video_slider'           => new Modules\Video_Mapper(),
			'et_pb_audio'                  => new Modules\Audio_Mapper(),
			'et_pb_gallery'                => new Modules\Gallery_Mapper(),
			'et_pb_toggle'                 => new Modules\Toggle_Mapper(),
			'et_pb_accordion'              => new Modules\Toggle_Mapper(),
			'et_pb_social_media_follow'    => new Modules\Social_Follow_Mapper(),
			'et_pb_search'                 => $post,
			'et_pb_login'                  => $post,
			'et_pb_post_title'             => $post,
			'et_pb_fullwidth_post_title'   => $post,
			'et_pb_post_content'           => $post,
			'et_pb_fullwidth_post_content' => $post,
			'et_pb_post_nav'               => $post,
			'et_pb_comments'               => $post,
			'et_pb_menu'                   => $post,
			'et_pb_fullwidth_menu'         => $post,
			'et_pb_sidebar'                => $post,
			'et_pb_blog'                   => $query,
			'et_pb_portfolio'              => $query,
			'et_pb_filterable_portfolio'   => $query,
			'et_pb_fullwidth_portfolio'    => $query,
			'et_pb_post_slider'            => $query,
			'et_pb_fullwidth_post_slider'  => $query,
			'et_pb_blurb'                  => new Modules\Blurb_Mapper(),
			'et_pb_cta'                    => new Modules\Cta_Mapper(),
			'et_pb_fullwidth_header'       => new Modules\Fullwidth_Header_Mapper(),
			'et_pb_testimonial'            => new Modules\Testimonial_Mapper(),
			'et_pb_team_member'            => new Modules\Team_Member_Mapper(),
			'et_pb_pricing_tables'         => new Modules\Pricing_Tables_Mapper(),
			'et_pb_tabs'                   => new Modules\Tabs_Mapper(),
			'et_pb_slider'                 => new Modules\Slider_Mapper(),
			'et_pb_fullwidth_slider'       => new Modules\Slider_Mapper(),
			'et_pb_counters'               => $count,
			'et_pb_number_counter'         => $count,
			'et_pb_circle_counter'         => $count,
			'et_pb_countdown_timer'        => $count,
			'et_pb_map'                    => new Modules\Map_Mapper(),
			'et_pb_fullwidth_map'          => new Modules\Map_Mapper(),
			'et_pb_contact_form'           => new Modules\Form_Mapper(),
			'et_pb_signup'                 => new Modules\Form_Mapper(),
			'et_pb_shop'                   => new Modules\Shop_Mapper(),
		);

		foreach ( $map as $tag => $mapper ) {
			$registry->register( $tag, $mapper );
		}

		if ( function_exists( 'apply_filters' ) ) {
			/**
			 * Filters the map of Divi tag to mapper.
			 *
			 * @param array<string,Mapper> $mappers Mappers.
			 */
			$filtered = apply_filters( 'edh_dg_mappers', $registry->mappers );
			if ( is_array( $filtered ) ) {
				$registry->mappers = array_filter(
					$filtered,
					static function ( $mapper ) {
						return $mapper instanceof Mapper;
					}
				);
			}
		}

		return $registry;
	}

	/**
	 * Registers a mapper.
	 *
	 * @param string $tag    Divi tag.
	 * @param Mapper $mapper Mapper.
	 */
	public function register( $tag, Mapper $mapper ) {
		$this->mappers[ $tag ] = $mapper;
	}

	/**
	 * Gets the mapper for a tag.
	 *
	 * @param string $tag Divi tag.
	 * @return Mapper
	 */
	public function get( $tag ) {
		return isset( $this->mappers[ $tag ] ) ? $this->mappers[ $tag ] : $this->fallback;
	}

	/**
	 * Tells if a tag has a mapper of its own.
	 *
	 * @param string $tag Divi tag.
	 * @return bool
	 */
	public function has( $tag ) {
		return isset( $this->mappers[ $tag ] );
	}
}
