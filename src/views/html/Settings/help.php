<?php 

/**
 * Help Settings View
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2015, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if (! defined('ABSPATH')) {
    exit;
}

/*
    This file is part of Gravity PDF.

    Gravity PDF Copyright (C) 2015 Blue Liquid Designs

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

?>

<?php $this->tabs(); ?>
        
        
<div id="pdfextended-settings">    
	<div class="wrap about-wrap">
	  <h1><?php _e('Getting Help With Gravity PDF', 'pdfexended'); ?></h1>
	  <div class="about-text"><?php _e('This is your portal to find quality help, support and documentation for Gravity PDF', 'pdfextended'); ?></div>
	  
	  <div id="search-knowledgebase">	    
	    <div id="search-results">	   
	      <div id="dashboard_primary" class="metabox-holder">      

	            <div id="documentation-api" class="postbox">   
	              <h3 class="hndle">
	                <span>Documentation</span>
	                <span class="spinner"></span>
	              </h3>
	              <div class="inside rss-widget">
	              	<ul></ul>
	              </div>
	            </div>  

	            <div id="forum-api" class="postbox ">   
	              <h3 class="hndle">
	                <span>Support Forum</span>
	                <span class="spinner"></span>
	              </h3>
	              <div class="inside rss-widget">
	                <ul></ul>
	              </div>
	            </div>  

	 
	      </div><!-- close #dashboard_primary -->	      
	    </div><!-- close #search-results -->
	  </div><!-- close #search-knowledgebase -->

	  <div class="hr-divider"></div>
	  
      <div id="support-wrapper" class="metabox-holder">
	        <div class="help-container">
	            <?php do_meta_boxes( 'pdf-help-and-support', 'row-1', '' ); ?>
	        </div><!-- close postbox-container -->

	        <div class="help-container">
	          	<?php do_meta_boxes( 'pdf-help-and-support', 'row-2', '' ); ?>
	        </div><!-- close postbox-container -->
      </div><!-- close metabox-holder -->
	</div><!-- close wrap about-wrap -->


	<?php do_action('pdf-settings-help'); ?>	                             
</div><!-- close #pdfextended-settings -->

<script type="text/template" id="GravityPDFSearchResultsForum">    
    <% _.each(collection, function (c) { %>
      <li>
        <a href="<%= url %>t/<%= c.get('slug') %>/<%= c.get('id') %>" class="rsswidget"><%= c.get('fancy_title') %></a> (<%= c.get('views') %>) <span class="rss-date">Last Updated <%= _.template.formatdate(c.get('last_posted_at')) %></span>
        <div class="rssSummary"></div>
      </li>
    <% }); %>

    <% if(collection.length === 0) { %>
      <li>No topics found for your search.</li>      
    <% } %>
</script>

<script type="text/template" id="GravityPDFSearchResultsDocumentation">    
    <% _.each(collection, function (c) { %>
      <li>
        <a href="<%= c.get('link') %>" class="rsswidget"><%= c.get('terms').documentation_group[0].name %> - <%= c.get('title') %></a> <span class="rss-date">Last Updated <%= _.template.formatdate(c.get('modified')) %></span>
        <div class="rssSummary"></div>
      </li>
    <% }); %>

    <% if(collection.length === 0) { %>
      <li>No topics found for your search.</li>      
    <% } %>
</script>	