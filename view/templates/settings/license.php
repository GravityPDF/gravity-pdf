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

  <h2>Manage your Addon License Keys</h2>

  <?php 

  $addons = GFPDF_Core::$addon;
  
  if(sizeof($addons) > 0): ?>
    <form method="post" action="<?php echo PDF_SETTINGS_URL; ?>&amp;tab=<?php echo PDF_Common::get('tab'); ?>">
        <?php wp_nonce_field('gfpdfe_license_nonce','gfpdfe_license_nonce_field'); ?>
        <?php foreach($addons as $addon): echo $addon['license_status']; ?>
            <p>
                <label for="<?php echo $addon['id']; ?>"><strong><?php echo $addon['name']; ?></strong></label>
                <input id="<?php echo $addon['id']; ?>" name="<?php echo $addon['id']; ?>" value="<?php echo $addon['license_key']; ?>" />
                
                <?php if($addon['license_status'] == 'valid'): ?>                
                  <?php /* output tick because it's a valid license */ ?>
                  <span class="deactivate"><a href="<?php echo PDF_SETTINGS_URL; ?>&amp;tab=<?php echo PDF_Common::get('tab'); ?>&amp;deactivate=<?php echo $addon['id']; ?>&amp;nonce=<?php echo wp_create_nonce('gfpdfe_deactive_license'); ?>">Deactivate License</a></span>
                  <br />
                  <span class="expires">Your license expires on <?php echo date('F j, Y', strtotime($addon['license_expires'])); ?>. Renew now.</span>
                <?php else: ?>
                  <?php if($addon['license_status'] && $addon['license_status'] != 'deactivated'): ?>
                      License not valid or expired.
                  <?php endif; ?>                    
                <?php endif; ?>
            </p>
        <?php endforeach; ?>

        <p>
            <input type="submit" name="submit" value="Update" />
        </p>
    </form>
  <?php endif; ?>