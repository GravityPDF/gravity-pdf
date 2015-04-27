<div class="wrap about-wrap">
  <h1><?php _e('Getting Help With Gravity PDF', 'pdfexended'); ?></h1>
  <div class="about-text"><?php _e('This is your portal to find quality help, support and documentation for Gravity PDF', 'pdfextended'); ?></div>
  <div id="search-knowledgebase">
    <!--<input  type="text" placeholder="&#xf002;  <?php _e('Search the Gravity PDF Knowledgebase...', 'pdfextended'); ?>" autofocus />-->


    <div id="search-results">
    
      <div id="dashboard_primary" class="metabox-holder">      


            <div id="documentation-api" class="postbox ">   
              <h3 class="hndle">
                <span>Documentation</span>
                <span class="spinner"></span>
              </h3>
              <div class="inside rss-widget">
                  <ul>
                    <li>
                      <a href="https://wordpress.org/news/2015/04/powell/" class="rsswidget">WordPress 4.2 “Powell”</a> <span class="rss-date">April 23, 2015</span>
                      <div class="rssSummary">Version 4.2 of WordPress, named “Powell” in honor of jazz pianist Bud Powell, is available for download or update in your WordPress dashboard. New features in 4.2 help you communicate and share, globally. An easier way to share content Clip it, edit it, publish it. Get familiar with the new and improved Press This. From […]</div>
                    </li>

                    <li>
                      <a href="https://wordpress.org/news/2015/04/powell/" class="rsswidget">WordPress 4.2 “Powell”</a> <span class="rss-date">April 23, 2015</span>
                      <div class="rssSummary">Version 4.2 of WordPress, named “Powell” in honor of jazz pianist Bud Powell, is available for download or update in your WordPress dashboard. New features in 4.2 help you communicate and share, globally. An easier way to share content Clip it, edit it, publish it. Get familiar with the new and improved Press This. From […]</div>
                    </li>

                    <li>
                      <a href="https://wordpress.org/news/2015/04/powell/" class="rsswidget">WordPress 4.2 “Powell”</a> <span class="rss-date">April 23, 2015</span>
                      <div class="rssSummary">Version 4.2 of WordPress, named “Powell” in honor of jazz pianist Bud Powell, is available for download or update in your WordPress dashboard. New features in 4.2 help you communicate and share, globally. An easier way to share content Clip it, edit it, publish it. Get familiar with the new and improved Press This. From […]</div>
                    </li>                    
                  </ul> 

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


<script type="text/template" id="GravityPDFSearchResults">    
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