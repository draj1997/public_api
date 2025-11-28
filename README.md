Public Info Module (SpaceX API Demo)

**Module Name:**
public_info

**Drupal Version Compatibility:**
Drupal 10 / Drupal 11

**PHP Version:**
PHP 8.1 or above

**Module Purpose:**
This custom module demonstrates:
- Consuming a public API using a custom service (SpaceX Launches API)
- Server-side caching using Cache API (configurable TTL)
- Configuration form (cache TTL + default result count)
- Refresh link on the page which invalidates the cache (CSRF-protected route) and refetches immediately.
- Pagination using Drupal's PagerManager service
- Custom permission and protected route (/public-info)
- Twig theming via hook_theme()
- CSRF-protected Refresh feature (manual cache invalidation)
- Optional block with per-instance settings
- menu links for the config and page launch
- PSR-4 autoloading via composer.json

**Installation:**
1. Place the module inside:
   /web/modules/custom/public_info

2. Clear caches:
   drush cr
   OR
   /admin/config/development/performance

3. Enable the module:
   drush en public_info
   OR
   /admin/modules

4. Assign permission:
   "Access public info page"
   (Roles → Permissions)

**Configuration:**
Visit: 
  /admin/config/services/public-info (or from the menu link inside -> webservices -> Public info settings

You can configure:
- Cache TTL (minutes)
- Number of launches displayed on the page (default limit)

**Usage:**
Access the main page:
  /public-info (or from menu inside config->spaceX launches)

**Features on this page:**
- Displays latest SpaceX launches (paginated)
- Each page cached separately via cache context
- “Refresh now” button invalidates cache tag public_info:launches

**Pagination:**
Implemented using Drupal\Core\Pager\PagerManager:
- createPager($total, $items_per_page)
- Rendered through 'pager' -> #type = 'pager'
- Cache context: url.query_args:page
  
**Blocks:**
Provides a block "Public Info Block" that allows:
- Per-block launch limit
- Per-block cache TTL

**Caching:**
Data-level caching:
- CID: public_info:launches_all
- Tag: public_info:launches
- TTL configurable

**Render-level caching:**
- Max-age
- Contexts (user.permissions, url.query_args:page)
- Tags (public_info:launches)

**Error Handling:**
Any API/network failure:
- Logs to channel logger.channel.public_info
- Displays a friendly fallback message

**Manual Cache Refresh:**
Route: /public-info/refresh
- CSRF protected
- Invalidates cache tag public_info:launches
- Refetches API on next load

**Assumptions:**
- Site uses a standard Drupal theme with pager support
- Devel is not required
- No API key required (SpaceX API is unauthenticated)

**File Structure:**
public_info/
  public_info.info.yml
  public_info.routing.yml
  public_info.links.menu.yml
  public_info.libraries.yml
  public_info.permissions.yml
  public_info.services.yml
  public_info.module
  composer.json
  config/
    /install
      public_info.settings.yml
    /schema
      public_info.schema.yml  
  css/
    public_info.css
  src/
    Controller/PublicInfoController.php
    Service/PublicInfoApiClient.php
    Plugin/Block/PublicInfoBlock.php
    Form/PublicInfoConfigForm.php
  templates/
    public-info.html.twig

    
End of README
