<?php 
/**
 *
 * Avaialable variables
 * - DateTime $checkInDate
 * - DateTime $checkOutDate
 * - int $adults
 * - int $children
 * - bool $isShowGallery
 * - bool $isShowImage
 * - bool $isShowTitle
 * - bool $isShowExcerpt
 * - bool $isShowDetails
 * - bool $isShowPrice
 * - bool $isShowViewButton
 * - bool $isShowBookButton
 *
 * @version 2.0.0
 */
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

if ( post_password_required() ) {
	$isShowGallery = $isShowImage = $isShowDetails = $isShowPrice = $isShowViewButton = $isShowBookButton = false;
}

do_action( 'mphb_sc_search_results_before_room' );

$wrapperClass = apply_filters( 'mphb_sc_search_results_room_type_class', join( ' ', mphb_tmpl_get_filtered_post_class( 'mphb-room-type' ) ) );

?>
<div class="appartment-box mb-30 <?php echo esc_attr( $wrapperClass ); ?>">

  <?php do_action( 'mphb_sc_search_results_room_top' ); ?>

  <div class="row no-gutters justify-content-center">
    <div class="col-lg-5 col-md-10">
      <div class="appartment-img-wrap">
        <?php  //if ( $isShowGallery && mphb_tmpl_has_room_type_gallery() ): ?>
          <!-- <div class=""> -->
          <?php 
            /**
             * @hooked \MPHB\Views\LoopRoomTypeView::renderGallery - 10
             */
            //do_action( 'mphb_sc_search_results_render_gallery' );
          ?>
        <?php //endif; ?>
        <?php //if($isShowImage && has_post_thumbnail()): ?>
          <?php 
              $backgroundImg = 'https://images.unsplash.com/photo-1445991842772-097fea258e7b?ixlib=rb-1.2.1&ixid=MnwxMjA3fDB8MHxzZWFyY2h8Mnx8cGxhY2Vob2xkZXIlMjBob3RlbHxlbnwwfHwwfHw%3D&auto=format&fit=crop&w=800&q=60';
              if ( $isShowImage && has_post_thumbnail() ) {
                $backgroundImg = wp_get_attachment_image_src( get_post_thumbnail_id(), 'full' )[0];
              }
            ?>
          <div class="appartment-img" style="background-image: url(<?= $backgroundImg ?>);">
        <?php //endif; ?>
        </div>
      </div>
    </div>
    <div class="col-lg-7 col-md-10 appartment-desc">
      <div class="row align-items-center">
        <?php if ( $isShowTitle || $isShowExcerpt || $isShowDetails || $isShowPrice || $isShowViewButton || $isShowBookButton ): ?>
          <div class="col-sm-8">
            <?php 

              if ( $isShowDetails ) {
                /**
                 * @hooked \MPHB\Views\LoopRoomTypeView::renderAttributes - 10
                 */
                do_action( 'mphb_sc_search_results_render_details' );
              }

              do_action( 'mphb_sc_search_results_before_info' );

              if ( $isShowTitle ) {
                /**
                 * @hooked \MPHB\Views\LoopRoomTypeView::renderTitle - 10
                 */
                do_action( 'mphb_sc_search_results_render_title' );
              }

              if ( $isShowExcerpt ) {
                /**
                 * @hooked \MPHB\Views\LoopRoomTypeView::renderExcerpt - 10
                 */
                do_action( 'mphb_sc_search_results_render_excerpt' );
              }
            ?>
          </div>
          <div class="col-sm-4">
            <div class="text-sm-center">
              <?php 

                if ( $isShowPrice ) {
                  /**
                   * @hooked \MPHB\Views\LoopRoomTypeView::renderPriceForDates - 10
                   */
                  do_action( 'mphb_sc_search_results_render_price', $checkInDate, $checkOutDate );
                }
            
                if ( $isShowBookButton ) {
                  /**
                   * @hooked \MPHB\Views\LoopRoomTypeView::renderBookButton - 10
                   */
                  do_action( 'mphb_sc_search_results_render_book_button' );
                }
            
                do_action( 'mphb_sc_search_results_after_info' );

              ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
	<?php do_action( 'mphb_sc_search_results_room_bottom' ); ?>
</div>
<?php 
do_action( 'mphb_sc_search_results_after_room' );
