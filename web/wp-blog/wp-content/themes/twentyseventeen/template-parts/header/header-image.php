<?php
/**
 * Displays header media
 *
 * @package WordPress
 * @subpackage Twenty_Seventeen
 * @since 1.0
 * @version 1.0
 */

?>
<style>
#masthead {
    height: 100px;
    background-color: white;
    border-bottom: 1px solid #c4c9cf;
}
.main-menu {
    padding-top: 20px;
}
</style>

<div class="custom-header">

    <div class="wrap main-menu">
        <img src="<?php echo esc_url( home_url( '/' ) ); ?>/wp-content/themes/twentyseventeen/assets/images/logo.png" width="200" alt="El blog de ammana">
    </div><!-- .wrap -->

</div><!-- .custom-header -->
