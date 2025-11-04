// schedula-menu-fix.js
;(function ($) {
  // Pass jQuery as $
  $(document).ready(function () {
    // Get the current URL's hash fragment (e.g., "#/appointments")
    let currentHash = window.location.hash

    // If there's no hash, default to the dashboard's expected hash
    // The dashboard has a slug 'schedula', and you've set 'schedula#/'
    if (!currentHash || currentHash === '#') {
      currentHash = '#/' // Normalize for dashboard
    }
    $('#toplevel_page_schesab .wp-submenu.wp-submenu-wrap li')
      .parent()
      .find('li')
      .removeClass('current')
    // Iterate through each submenu item
    $('#toplevel_page_schesab .wp-submenu.wp-submenu-wrap li').each(
      function () {
        const $this = $(this)
        // Get the URL from the <a> tag within the list item
        const href = $this.find('a').attr('href')

        if (href) {
          // Extract the hash part from the href
          // Example: 'admin.php?page=schedula#/appointments' -> '#/appointments'
          const submenuHash = href.substring(href.indexOf('#'))

          // Compare with the current page's hash
          if (submenuHash === currentHash) {
            $this.addClass('current')
            // Important: Also add 'current' to the parent <a> for the main menu item
            // if it's not the dashboard page (which is handled by WordPress usually).
            // This often handles cases where the top-level menu isn't marked 'current'
            // when a submenu is active.
            $('#toplevel_page_schesab > a').addClass('current')
            return false // Break the .each loop once found
          } else {
            if (currentHash == '#/ServiceFormReservation') {
              $this.addClass('current')
              $('#toplevel_page_schesab > a').addClass('current')
            }
            if (currentHash == '#/') {
              // Special case for dashboard
              if (
                href.indexOf('page=schesab') !== -1 &&
                href.indexOf('#') === -1
              ) {
                $this.addClass('current')
                $('#toplevel_page_schesab > a').addClass('current')
              }
            }
          }
        }
      }
    )
    $('#toplevel_page_schesab .wp-submenu.wp-submenu-wrap li').click(
      function () {
        // Remove 'current' class from all sibling list items
        $(this).parent().find('li').removeClass('current')

        // Add 'current' class to the clicked list item
        $(this).addClass('current')
      }
    )
  })
})(jQuery) // Pass the global jQuery object
