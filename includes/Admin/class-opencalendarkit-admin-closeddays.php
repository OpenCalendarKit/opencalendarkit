<?php
/**
 * Closed-day and exception-day administration.
 *
 * @package OpenCalendarKit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages closed days, exceptional openings, and the admin calendar.
 */
class OpenCalendarKit_Admin_ClosedDays {
	private const STATE_CLOSED = 'closed';
	private const STATE_OPEN   = 'open';

	/**
	 * Return the allowed HTML for sanitized admin markup fragments.
	 *
	 * @return array<string, array<string, bool>>
	 */
	private static function get_allowed_admin_html(): array {
		$allowed_html = wp_kses_allowed_html( 'post' );

		$allowed_html['div'] = array_merge(
			$allowed_html['div'] ?? array(),
			array(
				'id'                               => true,
				'class'                            => true,
				'style'                            => true,
				'data-openkit-admin-calendar-root' => true,
				'data-openkit-admin-calendar'      => true,
				'data-month'                       => true,
				'data-date'                        => true,
				'data-reason'                      => true,
				'data-closed-event'                => true,
				'data-closed-rule'                 => true,
				'data-open-override'               => true,
			)
		);

		$allowed_html['a'] = array_merge(
			$allowed_html['a'] ?? array(),
			array(
				'class'             => true,
				'href'              => true,
				'data-target-month' => true,
			)
		);

		$allowed_html['span'] = array_merge(
			$allowed_html['span'] ?? array(),
			array(
				'class' => true,
			)
		);

		$allowed_html['button'] = array(
			'id'                 => true,
			'class'              => true,
			'type'               => true,
			'style'              => true,
			'aria-label'         => true,
			'data-date'          => true,
			'data-reason'        => true,
			'data-closed-event'  => true,
			'data-closed-rule'   => true,
			'data-open-override' => true,
		);

		$allowed_html['form'] = array(
			'id' => true,
		);

		$allowed_html['label'] = array();

		$allowed_html['input'] = array(
			'type'  => true,
			'name'  => true,
			'value' => true,
		);

		$allowed_html['h1'] = array();

		return $allowed_html;
	}

	/**
	 * Build the translated month title.
	 *
	 * @param DateTimeInterface $date     Month date.
	 * @param DateTimeZone      $timezone Timezone to use.
	 * @return string
	 */
	private static function get_month_title( DateTimeInterface $date, DateTimeZone $timezone ) {
		if ( function_exists( 'wp_date' ) ) {
			return wp_date( 'F Y', $date->getTimestamp(), $timezone );
		}

		$local_date = new DateTime( '@' . $date->getTimestamp() );
		$local_date->setTimezone( $timezone );

		return $local_date->format( 'F Y' );
	}

	/**
	 * Normalize an exception state value.
	 *
	 * @param string $state Candidate state.
	 * @return string
	 */
	private static function normalize_exception_state( $state ): string {
		return self::STATE_OPEN === $state ? self::STATE_OPEN : self::STATE_CLOSED;
	}

	/**
	 * Build the post title for an exception day.
	 *
	 * @param string $date  Exception date.
	 * @param string $state Exception state.
	 * @return string
	 */
	private static function build_exception_title( string $date, string $state ): string {
		if ( self::STATE_OPEN === $state ) {
			/* translators: %s: exceptional opening date in YYYY-MM-DD format. */
			return sprintf( __( 'Open exceptionally: %s', 'open-calendar-kit' ), $date );
		}

		/* translators: %s: closed day date in YYYY-MM-DD format. */
		return sprintf( __( 'Closed: %s', 'open-calendar-kit' ), $date );
	}

	/**
	 * Normalize the requested calendar month.
	 *
	 * @param mixed        $month    Requested month value.
	 * @param DateTimeZone $timezone Timezone to use.
	 * @return DateTime
	 */
	private static function normalize_month( $month, DateTimeZone $timezone ): DateTime {
		if ( is_string( $month ) && preg_match( '/^\d{4}-\d{2}$/', $month ) ) {
			$date = DateTime::createFromFormat( '!Y-m', $month, $timezone );
			if ( $date ) {
				return $date;
			}
		}

		return new DateTime( 'first day of this month', $timezone );
	}

	/**
	 * Register the closed-day custom post type and related hooks.
	 *
	 * @return void
	 */
	public static function register_cpt() {
		OpenCalendarKit_I18n::with_locale(
			function () {
				$labels = array(
					'name'          => __( 'Closed Days', 'open-calendar-kit' ),
					'singular_name' => __( 'Closed Day', 'open-calendar-kit' ),
				);

				register_post_type(
					OpenCalendarKit_Plugin::CPT_CLOSED_DAY,
					array(
						'labels'       => $labels,
						'public'       => false,
						'show_ui'      => true,
						'menu_icon'    => 'dashicons-no-alt',
						'supports'     => array( 'title' ),
						'show_in_menu' => false,
					)
				);

				add_filter( 'manage_' . OpenCalendarKit_Plugin::CPT_CLOSED_DAY . '_posts_columns', array( __CLASS__, 'cols' ) );
				add_action( 'manage_' . OpenCalendarKit_Plugin::CPT_CLOSED_DAY . '_posts_custom_column', array( __CLASS__, 'col_content' ), 10, 2 );
				add_filter( 'manage_edit-' . OpenCalendarKit_Plugin::CPT_CLOSED_DAY . '_sortable_columns', array( __CLASS__, 'sortable' ) );
				add_action( 'pre_get_posts', array( __CLASS__, 'default_order' ) );

				add_action( 'wp_ajax_' . OpenCalendarKit_Plugin::AJAX_SAVE_CLOSED_DAY, array( __CLASS__, 'ajax_save' ) );
				add_action( 'wp_ajax_' . OpenCalendarKit_Plugin::AJAX_DELETE_CLOSED_DAY, array( __CLASS__, 'ajax_delete' ) );
				add_action( 'wp_ajax_' . OpenCalendarKit_Plugin::AJAX_SAVE_OPEN_EXCEPTION, array( __CLASS__, 'ajax_save_open_exception' ) );
				add_action( 'wp_ajax_' . OpenCalendarKit_Plugin::AJAX_DELETE_OPEN_EXCEPTION, array( __CLASS__, 'ajax_delete_open_exception' ) );
			}
		);
	}

	/**
	 * Filter the list table columns for the closed-day post type.
	 *
	 * @param array<string, string> $columns Existing columns.
	 * @return array<string, string>
	 */
	public static function cols( $columns ) {
		return OpenCalendarKit_I18n::with_locale(
			function () use ( $columns ) {
				$new_columns               = array();
				$new_columns['cb']         = $columns['cb'];
				$new_columns['title']      = __( 'Title', 'open-calendar-kit' );
				$new_columns['_bk_date']   = __( 'Date', 'open-calendar-kit' );
				$new_columns['_bk_reason'] = __( 'Reason', 'open-calendar-kit' );
				$new_columns['_edit']      = __( 'Actions', 'open-calendar-kit' );

				return $new_columns;
			}
		);
	}

	/**
	 * Render custom column content.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public static function col_content( $column, $post_id ) {
		OpenCalendarKit_I18n::with_locale(
			function () use ( $column, $post_id ) {
				if ( '_bk_date' === $column ) {
					echo esc_html( get_post_meta( $post_id, '_bk_date', true ) );
					return;
				}

				if ( '_bk_reason' === $column ) {
					echo esc_html( get_post_meta( $post_id, '_bk_reason', true ) );
					return;
				}

				if ( '_edit' === $column ) {
					$url = admin_url( 'post.php?post=' . (int) $post_id . '&action=edit' );
					echo '<a class="button" href="' . esc_url( $url ) . '">' . esc_html__( 'Edit', 'open-calendar-kit' ) . '</a>';
				}
			}
		);
	}

	/**
	 * Mark custom columns as sortable.
	 *
	 * @param array<string, string> $columns Existing sortable columns.
	 * @return array<string, string>
	 */
	public static function sortable( $columns ) {
		$columns['_bk_date'] = '_bk_date';

		return $columns;
	}

	/**
	 * Apply the default list-table ordering for closed days.
	 *
	 * @param WP_Query $query Current query.
	 * @return void
	 */
	public static function default_order( $query ) {
		if ( ! is_admin() || $query->get( 'post_type' ) !== OpenCalendarKit_Plugin::CPT_CLOSED_DAY ) {
			return;
		}

		if ( ! $query->get( 'orderby' ) ) {
			$query->set( 'meta_key', '_bk_date' );
			$query->set( 'orderby', 'meta_value' );
			$query->set( 'order', 'ASC' );
			return;
		}

		if ( '_bk_date' === $query->get( 'orderby' ) ) {
			$query->set( 'meta_key', '_bk_date' );
			$query->set( 'orderby', 'meta_value' );
		}
	}

	/**
	 * Register the closed-day metabox.
	 *
	 * @return void
	 */
	public static function register_metabox() {
		OpenCalendarKit_I18n::with_locale(
			function () {
				add_meta_box(
					'openkit_closed_day_meta',
					__( 'Closed Day Details', 'open-calendar-kit' ),
					array( __CLASS__, 'render_metabox' ),
					OpenCalendarKit_Plugin::CPT_CLOSED_DAY,
					'normal',
					'default'
				);
			}
		);
	}

	/**
	 * Render the closed-day metabox.
	 *
	 * @param WP_Post $post Current post.
	 * @return void
	 */
	public static function render_metabox( $post ) {
		OpenCalendarKit_I18n::with_locale(
			function () use ( $post ) {
				wp_nonce_field( OpenCalendarKit_Plugin::NONCE_CLOSED_DAY_META, 'openkit_closed_day_meta_nonce' );
				$date   = get_post_meta( $post->ID, '_bk_date', true );
				$reason = get_post_meta( $post->ID, '_bk_reason', true );
				?>
				<input type="hidden" name="openkit_state" value="<?php echo esc_attr( self::normalize_exception_state( get_post_meta( $post->ID, '_bk_state', true ) ) ); ?>" />
				<p>
					<label for="openkit_date"><strong><?php esc_html_e( 'Date (YYYY-MM-DD)', 'open-calendar-kit' ); ?></strong></label><br/>
					<input type="date" id="openkit_date" name="openkit_date" value="<?php echo esc_attr( $date ); ?>" />
				</p>
				<p>
					<label for="openkit_reason"><strong><?php esc_html_e( 'Reason (optional)', 'open-calendar-kit' ); ?></strong></label><br/>
					<input type="text" id="openkit_reason" name="openkit_reason" value="<?php echo esc_attr( $reason ); ?>" class="regular-text" />
				</p>
				<?php
			}
		);
	}

	/**
	 * Save closed-day metabox data.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public static function save_metabox( $post_id ) {
		$nonce = isset( $_POST['openkit_closed_day_meta_nonce'] )
			? sanitize_text_field( wp_unslash( $_POST['openkit_closed_day_meta_nonce'] ) )
			: '';

		if ( ! $nonce || ! wp_verify_nonce( $nonce, OpenCalendarKit_Plugin::NONCE_CLOSED_DAY_META ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$date   = isset( $_POST['openkit_date'] ) ? sanitize_text_field( wp_unslash( $_POST['openkit_date'] ) ) : '';
		$reason = isset( $_POST['openkit_reason'] ) ? sanitize_text_field( wp_unslash( $_POST['openkit_reason'] ) ) : '';
		$state  = isset( $_POST['openkit_state'] )
			? self::normalize_exception_state( sanitize_text_field( wp_unslash( $_POST['openkit_state'] ) ) )
			: self::normalize_exception_state( get_post_meta( $post_id, '_bk_state', true ) );

		update_post_meta( $post_id, '_bk_date', $date );
		update_post_meta( $post_id, '_bk_reason', $reason );
		update_post_meta( $post_id, '_bk_state', $state );

		if ( empty( get_the_title( $post_id ) ) && $date ) {
			wp_update_post(
				array(
					'ID'         => $post_id,
					'post_title' => self::build_exception_title( $date, $state ),
				)
			);
		}
	}

	/**
	 * Get all exception post IDs for a date.
	 *
	 * @param string $date Exception date.
	 * @return array<int>
	 */
	private static function get_exception_post_ids( $date ): array {
		$query = new WP_Query(
			array(
				'post_type'      => OpenCalendarKit_Plugin::CPT_CLOSED_DAY,
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'fields'         => 'ids',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Small admin-managed dataset keyed by date.
				'meta_key'       => '_bk_date',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Small admin-managed dataset keyed by date.
				'meta_value'     => $date,
			)
		);

		$ids = ! empty( $query->posts ) ? array_map( 'intval', $query->posts ) : array();
		wp_reset_postdata();

		return $ids;
	}

	/**
	 * Find an exception post for a date and state.
	 *
	 * @param string $date  Exception date.
	 * @param string $state Exception state.
	 * @return int
	 */
	private static function find_exception_post_id( $date, $state ) {
		$normalized_state = self::normalize_exception_state( $state );

		foreach ( self::get_exception_post_ids( $date ) as $post_id ) {
			$post_state = self::normalize_exception_state( get_post_meta( $post_id, '_bk_state', true ) );
			if ( $normalized_state === $post_state ) {
				return (int) $post_id;
			}
		}

		return 0;
	}

	/**
	 * Delete an exception post for a date and state.
	 *
	 * @param string $date  Exception date.
	 * @param string $state Exception state.
	 * @return void
	 */
	private static function delete_exception_post( $date, $state ): void {
		$post_id = self::find_exception_post_id( $date, $state );
		if ( $post_id ) {
			wp_delete_post( $post_id, true );
		}
	}

	/**
	 * Determine whether a date is explicitly marked closed.
	 *
	 * @param string $date Date in Y-m-d format.
	 * @return bool
	 */
	public static function is_closed_on( $date ) {
		return self::find_exception_post_id( (string) $date, self::STATE_CLOSED ) > 0;
	}

	/**
	 * Determine whether a date is explicitly marked open.
	 *
	 * @param string $date Date in Y-m-d format.
	 * @return bool
	 */
	public static function is_open_exception_on( $date ) {
		return self::find_exception_post_id( (string) $date, self::STATE_OPEN ) > 0;
	}

	/**
	 * Get the stored reason for a closed date.
	 *
	 * @param string $date Date in Y-m-d format.
	 * @return string
	 */
	public static function get_reason( $date ) {
		$post_id = self::find_exception_post_id( (string) $date, self::STATE_CLOSED );
		if ( ! $post_id ) {
			return '';
		}

		return trim( (string) get_post_meta( $post_id, '_bk_reason', true ) );
	}

	/**
	 * Register the calendar submenu page.
	 *
	 * @return void
	 */
	public static function register_menu() {
		OpenCalendarKit_I18n::with_locale(
			function () {
				add_submenu_page(
					OpenCalendarKit_Plugin::MENU_SLUG,
					__( 'Calendar', 'open-calendar-kit' ),
					__( 'Calendar', 'open-calendar-kit' ),
					OpenCalendarKit_Plugin::CAP_MANAGE,
					OpenCalendarKit_Plugin::PAGE_CALENDAR,
					array( __CLASS__, 'render_admin_page' )
				);
			}
		);
	}

	/**
	 * Render the admin calendar markup for a month.
	 *
	 * @param string $month Optional month override.
	 * @return string
	 */
	private static function render_calendar_markup( $month = '' ) {
		$timezone      = new DateTimeZone( wp_timezone_string() );
		$date          = self::normalize_month( $month, $timezone );
		$year          = (int) $date->format( 'Y' );
		$month_number  = (int) $date->format( 'n' );
		$days_in_month = (int) $date->format( 't' );

		$day_zero_to_n = static function ( int $day_zero ): int {
			return 0 === $day_zero ? 7 : $day_zero;
		};

		if ( function_exists( 'jddayofweek' ) && function_exists( 'cal_to_jd' ) ) {
			$first_day_zero = jddayofweek( cal_to_jd( CAL_GREGORIAN, $month_number, 1, $year ), 0 );
			$first_day_n    = $day_zero_to_n( $first_day_zero );
		} else {
			$first_day_n = (int) ( new DateTime( sprintf( '%04d-%02d-01', $year, $month_number ), $timezone ) )->format( 'N' );
		}

		$hours         = OpenCalendarKit_Admin_OpeningHours::get_hours();
		$today         = ( new DateTime( 'now', $timezone ) )->format( 'Y-m-d' );
		$get_hours_row = static function ( int $day_of_week_n ) use ( $hours ) {
			if ( isset( $hours[ $day_of_week_n ] ) && is_array( $hours[ $day_of_week_n ] ) ) {
				return $hours[ $day_of_week_n ];
			}

			$day_of_week_zero = ( $day_of_week_n + 6 ) % 7;
			if ( isset( $hours[ $day_of_week_zero ] ) && is_array( $hours[ $day_of_week_zero ] ) ) {
				return $hours[ $day_of_week_zero ];
			}

			return array( 'closed' => 0 );
		};

		$cells = array();
		for ( $day = 1; $day <= $days_in_month; $day++ ) {
			$cell_date = sprintf( '%04d-%02d-%02d', $year, $month_number, $day );

			if ( function_exists( 'jddayofweek' ) && function_exists( 'cal_to_jd' ) ) {
				$day_zero = jddayofweek( cal_to_jd( CAL_GREGORIAN, $month_number, $day, $year ), 0 );
				$day_n    = $day_zero_to_n( $day_zero );
			} else {
				$day_n = (int) ( new DateTime( $cell_date, $timezone ) )->format( 'N' );
			}

			$config          = $get_hours_row( $day_n );
			$closed_by_rule  = ! empty( $config['closed'] );
			$closed_by_event = self::is_closed_on( $cell_date );
			$open_override   = self::is_open_exception_on( $cell_date );
			$state           = ( $closed_by_event || ( $closed_by_rule && ! $open_override ) ) ? 'closed' : 'open';
			$past            = $cell_date < $today;
			$reason          = $closed_by_event ? self::get_reason( $cell_date ) : '';

			$cells[] = array(
				'day'           => $day,
				'date'          => $cell_date,
				'state'         => $state,
				'past'          => $past,
				'reason'        => $reason,
				'closed_event'  => $closed_by_event,
				'closed_rule'   => $closed_by_rule,
				'open_override' => $open_override,
			);
		}

		$next = ( clone $date )->modify( '+1 month' );
		$prev = ( clone $date )->modify( '-1 month' );

		ob_start();
		?>
		<div class="bkit-calendar bkit-admin-cal" data-openkit-admin-calendar="1" data-month="<?php echo esc_attr( $date->format( 'Y-m' ) ); ?>" style="max-width:380px;">
			<div class="bkit-cal-head">
				<a class="bkit-nav prev" href="<?php echo esc_attr( '#openkit-admin-month=' . $prev->format( 'Y-m' ) ); ?>" data-target-month="<?php echo esc_attr( $prev->format( 'Y-m' ) ); ?>">‹</a>
				<span class="bkit-cal-title"><?php echo esc_html( self::get_month_title( $date, $timezone ) ); ?></span>
				<a class="bkit-nav next" href="<?php echo esc_attr( '#openkit-admin-month=' . $next->format( 'Y-m' ) ); ?>" data-target-month="<?php echo esc_attr( $next->format( 'Y-m' ) ); ?>">›</a>
			</div>

			<div class="bkit-grid">
				<?php
				global $wp_locale;
				$weekday_abbreviations = array_values( $wp_locale->weekday_abbrev );
				$weekday_mon_first     = array_merge( array_slice( $weekday_abbreviations, 1 ), array( $weekday_abbreviations[0] ) );
				foreach ( $weekday_mon_first as $weekday ) {
					echo '<div class="bkit-cell bkit-wd">' . esc_html( $weekday ) . '</div>';
				}

				for ( $offset = 1; $offset < $first_day_n; $offset++ ) {
					echo '<div class="bkit-cell bkit-empty"></div>';
				}

				foreach ( $cells as $cell ) {
					$classes = 'bkit-cell day ' . ( $cell['past'] ? 'past' : $cell['state'] );
					printf(
						'<button class="%s" data-date="%s"%s%s%s%s type="button"><span class="num">%d</span></button>',
						esc_attr( $classes ),
						esc_attr( $cell['date'] ),
						'' !== $cell['reason'] ? ' data-reason="' . esc_attr( $cell['reason'] ) . '"' : '',
						' data-closed-event="' . esc_attr( $cell['closed_event'] ? '1' : '0' ) . '"',
						' data-closed-rule="' . esc_attr( $cell['closed_rule'] ? '1' : '0' ) . '"',
						' data-open-override="' . esc_attr( $cell['open_override'] ? '1' : '0' ) . '"',
						(int) $cell['day']
					);
				}
				?>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Render the full admin calendar page markup.
	 *
	 * @param string $month Optional month override.
	 * @return string
	 */
	private static function render_page_markup( $month = '' ) {
		ob_start();
		?>
		<div class="wrap" data-openkit-admin-calendar-root="1">
			<h1><?php echo esc_html__( 'Calendar', 'open-calendar-kit' ); ?></h1>
			<?php echo wp_kses( self::render_calendar_markup( $month ), self::get_allowed_admin_html() ); ?>
			<div id="bkit-closedday-modal" class="bkit-modal" style="display:none;">
				<div class="bkit-modal-box">
					<div class="bkit-modal-head">
						<span class="title"><?php esc_html_e( 'Set Closed Day', 'open-calendar-kit' ); ?></span>
						<button type="button" class="bkit-close" aria-label="<?php esc_attr_e( 'Close', 'open-calendar-kit' ); ?>">×</button>
					</div>
					<form id="bkit-closedday-form">
						<div class="row">
							<label><?php esc_html_e( 'Date', 'open-calendar-kit' ); ?></label>
							<input type="date" name="date" value="">
						</div>
						<div class="row">
							<label><?php esc_html_e( 'Reason (optional)', 'open-calendar-kit' ); ?></label>
							<input type="text" name="reason" value="">
						</div>
						<div class="actions">
							<button type="submit" class="button button-primary"><?php esc_html_e( 'Save closed day', 'open-calendar-kit' ); ?></button>
							<button id="bkit-open-day" type="button" class="button" style="display:none;">
								<?php esc_html_e( 'Open day again', 'open-calendar-kit' ); ?>
							</button>
							<button id="bkit-toggle-open-exception" type="button" class="button" style="display:none;"></button>
							<button id="bkit-cancel" type="button" class="button"><?php esc_html_e( 'Cancel', 'open-calendar-kit' ); ?></button>
						</div>
						<div class="bkit-feedback" style="display:none;"></div>
					</form>
				</div>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Render the calendar admin page.
	 *
	 * @return void
	 */
	public static function render_admin_page() {
		OpenCalendarKit_I18n::with_locale(
			function () {
				echo wp_kses( self::render_page_markup(), self::get_allowed_admin_html() );
			}
		);
	}

	/**
	 * Return a calendar month over AJAX.
	 *
	 * @return void
	 */
	public static function ajax_render_calendar_month() {
		OpenCalendarKit_I18n::with_locale(
			function () {
				check_ajax_referer( OpenCalendarKit_Plugin::NONCE_ADMIN, 'nonce' );

				if ( ! current_user_can( OpenCalendarKit_Plugin::CAP_MANAGE ) ) {
					wp_send_json_error( array( 'msg' => __( 'Not allowed', 'open-calendar-kit' ) ), 403 );
				}

				$month = isset( $_POST['month'] ) ? sanitize_text_field( wp_unslash( $_POST['month'] ) ) : '';
				if ( ! preg_match( '/^\d{4}-\d{2}$/', $month ) ) {
					wp_send_json_error( array( 'msg' => __( 'Invalid month', 'open-calendar-kit' ) ), 400 );
				}

				wp_send_json_success(
					array(
						'html' => self::render_page_markup( $month ),
					)
				);
			}
		);
	}

	/**
	 * Save a closed-day exception over AJAX.
	 *
	 * @return void
	 */
	public static function ajax_save() {
		OpenCalendarKit_I18n::with_locale(
			function () {
				check_ajax_referer( OpenCalendarKit_Plugin::NONCE_ADMIN, 'nonce' );

				if ( ! current_user_can( OpenCalendarKit_Plugin::CAP_MANAGE ) ) {
					wp_send_json_error( array( 'msg' => __( 'Not allowed', 'open-calendar-kit' ) ), 403 );
				}

				$date   = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : '';
				$reason = isset( $_POST['reason'] ) ? sanitize_text_field( wp_unslash( $_POST['reason'] ) ) : '';

				if ( ! $date || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
					wp_send_json_error( array( 'msg' => __( 'Invalid date', 'open-calendar-kit' ) ), 400 );
				}

				$post_id = self::find_exception_post_id( $date, self::STATE_CLOSED );
				if ( $post_id ) {
					update_post_meta( $post_id, '_bk_reason', $reason );
					update_post_meta( $post_id, '_bk_state', self::STATE_CLOSED );
					if ( empty( get_the_title( $post_id ) ) ) {
						wp_update_post(
							array(
								'ID'         => $post_id,
								'post_title' => self::build_exception_title( $date, self::STATE_CLOSED ),
							)
						);
					}

					$open_override_post_id = self::find_exception_post_id( $date, self::STATE_OPEN );
					if ( $open_override_post_id ) {
						wp_delete_post( $open_override_post_id, true );
					}

					wp_send_json_success( array( 'msg' => __( 'Updated closed day', 'open-calendar-kit' ) ) );
				}

				$post_id = wp_insert_post(
					array(
						'post_type'   => OpenCalendarKit_Plugin::CPT_CLOSED_DAY,
						'post_status' => 'publish',
						'post_title'  => self::build_exception_title( $date, self::STATE_CLOSED ),
					),
					true
				);

				if ( is_wp_error( $post_id ) ) {
					wp_send_json_error( array( 'msg' => $post_id->get_error_message() ), 500 );
				}

				update_post_meta( $post_id, '_bk_date', $date );
				update_post_meta( $post_id, '_bk_reason', $reason );
				update_post_meta( $post_id, '_bk_state', self::STATE_CLOSED );

				$open_override_post_id = self::find_exception_post_id( $date, self::STATE_OPEN );
				if ( $open_override_post_id ) {
					wp_delete_post( $open_override_post_id, true );
				}

				wp_send_json_success( array( 'msg' => __( 'Created closed day', 'open-calendar-kit' ) ) );
			}
		);
	}

	/**
	 * Delete a closed-day exception over AJAX.
	 *
	 * @return void
	 */
	public static function ajax_delete() {
		OpenCalendarKit_I18n::with_locale(
			function () {
				check_ajax_referer( OpenCalendarKit_Plugin::NONCE_ADMIN, 'nonce' );

				if ( ! current_user_can( OpenCalendarKit_Plugin::CAP_MANAGE ) ) {
					wp_send_json_error( array( 'msg' => __( 'Not allowed', 'open-calendar-kit' ) ), 403 );
				}

				$date = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : '';
				if ( ! $date || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
					wp_send_json_error( array( 'msg' => __( 'Invalid date', 'open-calendar-kit' ) ), 400 );
				}

				$post_id = self::find_exception_post_id( $date, self::STATE_CLOSED );
				if ( ! $post_id ) {
					wp_send_json_success( array( 'msg' => __( 'Day is already open', 'open-calendar-kit' ) ) );
				}

				wp_delete_post( $post_id, true );
				wp_send_json_success( array( 'msg' => __( 'Closed day removed (open again)', 'open-calendar-kit' ) ) );
			}
		);
	}

	/**
	 * Save an exceptional opening over AJAX.
	 *
	 * @return void
	 */
	public static function ajax_save_open_exception() {
		OpenCalendarKit_I18n::with_locale(
			function () {
				check_ajax_referer( OpenCalendarKit_Plugin::NONCE_ADMIN, 'nonce' );

				if ( ! current_user_can( OpenCalendarKit_Plugin::CAP_MANAGE ) ) {
					wp_send_json_error( array( 'msg' => __( 'Not allowed', 'open-calendar-kit' ) ), 403 );
				}

				$date = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : '';
				if ( ! $date || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
					wp_send_json_error( array( 'msg' => __( 'Invalid date', 'open-calendar-kit' ) ), 400 );
				}

				$post_id = self::find_exception_post_id( $date, self::STATE_OPEN );
				if ( $post_id ) {
					update_post_meta( $post_id, '_bk_state', self::STATE_OPEN );
					update_post_meta( $post_id, '_bk_reason', '' );
					self::delete_exception_post( $date, self::STATE_CLOSED );
					wp_send_json_success( array( 'msg' => __( 'Exceptional opening saved', 'open-calendar-kit' ) ) );
				}

				$post_id = wp_insert_post(
					array(
						'post_type'   => OpenCalendarKit_Plugin::CPT_CLOSED_DAY,
						'post_status' => 'publish',
						'post_title'  => self::build_exception_title( $date, self::STATE_OPEN ),
					),
					true
				);

				if ( is_wp_error( $post_id ) ) {
					wp_send_json_error( array( 'msg' => $post_id->get_error_message() ), 500 );
				}

				update_post_meta( $post_id, '_bk_date', $date );
				update_post_meta( $post_id, '_bk_reason', '' );
				update_post_meta( $post_id, '_bk_state', self::STATE_OPEN );
				self::delete_exception_post( $date, self::STATE_CLOSED );

				wp_send_json_success( array( 'msg' => __( 'Exceptional opening saved', 'open-calendar-kit' ) ) );
			}
		);
	}

	/**
	 * Delete an exceptional opening over AJAX.
	 *
	 * @return void
	 */
	public static function ajax_delete_open_exception() {
		OpenCalendarKit_I18n::with_locale(
			function () {
				check_ajax_referer( OpenCalendarKit_Plugin::NONCE_ADMIN, 'nonce' );

				if ( ! current_user_can( OpenCalendarKit_Plugin::CAP_MANAGE ) ) {
					wp_send_json_error( array( 'msg' => __( 'Not allowed', 'open-calendar-kit' ) ), 403 );
				}

				$date = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : '';
				if ( ! $date || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
					wp_send_json_error( array( 'msg' => __( 'Invalid date', 'open-calendar-kit' ) ), 400 );
				}

				$post_id = self::find_exception_post_id( $date, self::STATE_OPEN );
				if ( ! $post_id ) {
					wp_send_json_success( array( 'msg' => __( 'Exceptional opening already removed', 'open-calendar-kit' ) ) );
				}

				wp_delete_post( $post_id, true );
				wp_send_json_success( array( 'msg' => __( 'Exceptional opening removed', 'open-calendar-kit' ) ) );
			}
		);
	}
}
