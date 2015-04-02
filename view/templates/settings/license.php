<?php

 /*
  * Template: License
  * Module: Settings Page
  *
  */
  
  /*
   * Don't run if the correct class isn't present
   */
  if(!class_exists('GFPDF_Settings_Model'))
  {
	 exit;  
  }
  
  ?>

  <h3>
    <span>
      <i class="fa fa-key"></i>
      <?php _e('Manage your Addon License Keys', 'pdfextended'); ?>
    </span>
  </h3>

  <?php 

  global $gfpdfe_data;
  $addons = GFPDF_Core::$addon;
  
  if(sizeof($addons) > 0): ?>
    <form method="post" action="<?php echo PDF_SETTINGS_URL; ?>&amp;tab=<?php echo PDF_Common::get('tab'); ?>">
        <?php wp_nonce_field('gfpdfe_license_nonce','gfpdfe_license_nonce_field'); ?>
        <?php foreach($addons as $addon): ?>

        <?php 
        /*
         * Work our the messages
         */
        if($addon['license_status'] == 'valid')
        {
          /*
           * Check if we are within a month of expiry and prompt to repurchase
           */
          $renew = '';
          if(strtotime($addon['license_expires']) < strtotime('+1 Month'))
          {    
              $renew = sprintf(__('%sRenew your license%s and get a 20%% discount.', 'pdfextended'), '<a href="'. $gfpdfe_data->store_url . 'add-ons/checkout/?edd_license_key='. $addon['license_key'] .'&download_id=' . $addon['download_id'] .'">', '</a>');
          }
        }
        ?>          
            <p>
                <label for="<?php echo $addon['id']; ?>"><?php echo $addon['name']; ?></label>
                <span class="container">
                  <input id="<?php echo $addon['id']; ?>" name="<?php echo $addon['id']; ?>" type="text" value="<?php echo $addon['license_key']; ?>" />
                  
                <?php if($addon['license_status'] == 'valid'): ?>                
                  <i class="fa fa-check-circle gf_keystatus_valid"></i>
                  <span class="deactivate"><a href="<?php echo PDF_SETTINGS_URL; ?>&amp;tab=<?php echo PDF_Common::get('tab'); ?>&amp;deactivate=<?php echo $addon['id']; ?>&amp;nonce=<?php echo wp_create_nonce('gfpdfe_deactive_license'); ?>"><?php _e('Deactivate License', 'pdfextended'); ?></a></span>
                  <br />
                  <span class="expires"><?php echo sprintf(__('Your license expires on %s.', 'pdfextended'), date('F j, Y', strtotime($addon['license_expires']) ) ); ?> <?php echo $renew; ?></span>
                <?php elseif(strlen($addon['license_status']) > 0 && $addon['license_status'] != 'deactivated'): ?>                    
                    <i class="fa fa-exclamation-circle gf_keystatus_invalid"></i>
                    <?php if($addon['license_status'] && $addon['license_status'] == 'inactive'): ?>                                          
                        <span class="inactive"><?php echo __('License not currently activated. To continue getting updates please activate your license.', 'pdfextended'); ?></span>
                    <?php elseif($addon['license_status'] && $addon['license_status'] == 'no_activations_left'): ?>
                        <span class="limit"><?php echo __("You've reached the limit on the number of websites you're licensed to.", 'pdfextended'); ?> <?php echo sprintf(__('%sDeactivate an existing site%s or %scontact us to upgrade%s.', 'pdfextended'), '<a href="' . $gfpdfe_data->store_url . '/add-ons/add-ons/checkout/purchase-history/">', '</a>', '<a href="<?php echo $gfpdfe_data->store_url; ?>/contact/">', '</a>'); ?></span>
                    <?php elseif($addon['license_status'] && $addon['license_status'] != 'inactive'): ?>
                        <?php echo __('License not valid or expired.', 'pdfextended'); ?>                        
                        <span class="expired"><?php echo sprintf(__('Need a new license key? %sPurchase one now%s.', 'pdfextended'), '<a href="'. $gfpdfe_data->store_url .'add-ons/">', '</a>'); ?></span>
                    <?php endif; ?>
                <?php endif; ?>
                </span>
            </p>
        <?php endforeach; ?>

        <p>
            <input type="submit" class="button-primary gfbutton" name="submit" value="Update" />
        </p>
    </form>
  <?php endif; ?>