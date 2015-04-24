<?php

 /*
  * Template: License
  * Module: Settings Page
  *
  */

  /*
   * Don't run if the correct class isn't present
   */
  if (!class_exists('GFPDF_Settings_Model')) {
      exit;
  }

  ?>

  <h3>
    <span>
      <i class="fa fa-unlock-alt"></i>
      <?php _e('Manage your Addon License Keys', 'pdfextended'); ?>
    </span>
  </h3>

  <p><?php _e('Your Gravity PDF license key is used to verify your extensions, give you access to additional content, enable automatic updates and receive support.', 'pdfextended'); ?></p>

  <?php

  global $gfpdfe_data;
  $addons = $gfpdfe_data->addon;

  $deactivate_nonce = wp_create_nonce('gfpdfe_deactivate_license'); 

  if (sizeof($addons) > 0): ?>
    <form method="post" action="<?php echo $gfpdfe_data->settings_url; ?>&amp;tab=<?php echo PDF_Common::get('tab'); ?>">
        <?php wp_nonce_field('gfpdfe_license_nonce', 'gfpdfe_license_nonce_field'); ?>
        <?php foreach ($addons as $addon): ?>

        <?php
          /* Check if we are within a month of expiry and prompt to repurchase */
          $renew = '';
          if (strtotime($addon['license_expires']) < strtotime('+1 Month')) {
              $renew = sprintf(__('%sRenew your license%s and get a 20%% discount.', 'pdfextended'), '<a href="'.$gfpdfe_data->store_url.'add-ons/checkout/?edd_license_key='.$addon['license_key'].'">', '</a>');
          }

        ?>
            <p>
                <label for="<?php echo $addon['id']; ?>"><?php echo $addon['name']; ?></label>
                <span class="container">
                  <input id="<?php echo $addon['id']; ?>" name="<?php echo $addon['id']; ?>" type="text" value="<?php echo $addon['license_key']; ?>" style="max-width: 350px" <?php if ($addon['license_status'] == 'valid'): ?>readonly="readonly"<?php endif; ?> />

                <?php if ($addon['license_status'] == 'valid'): ?>
                  <i class="fa fa-check gf_keystatus_valid"></i> <span class="gf_keystatus_valid_text"><?php _e('Valid License', 'pdfextended'); ?></span>

                  <br />

                  <span class="expires">
                      <?php echo sprintf(__('Your license expires on %s.', 'pdfextended'), date('F j, Y', strtotime($addon['license_expires']))); ?> <?php echo $renew; ?>
                  </span>
<span class="deactivate"><a href="<?php echo $gfpdfe_data->settings_url; ?>&amp;tab=<?php echo PDF_Common::get('tab'); ?>&amp;deactivate=<?php echo $addon['id']; ?>&amp;nonce=<?php echo $deactivate_nonce; ?>"><?php _e('Deactivate License', 'pdfextended'); ?></a></span>

                <?php elseif (strlen($addon['license_status']) > 0 && $addon['license_status'] != 'deactivated'): ?>
                    <i class="fa fa-times gf_keystatus_invalid"></i>
                    <?php if ($addon['license_status'] && $addon['license_status'] == 'inactive'): ?>
                        <span class="inactive"><?php echo __('License not currently activated.', 'pdfextended'); ?></span>
                    <?php elseif ($addon['license_status'] && $addon['license_status'] == 'no_activations_left'): ?>
                        <span class="limit"><?php echo __("Activation Reached.", 'pdfextended'); ?></span>
                    <?php elseif ($addon['license_status'] == 'expired'): ?>
                        <span class="expires expired"><?php _e('Your license has expired.', 'pdfextended'); ?> <?php echo $renew; ?></span>
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
