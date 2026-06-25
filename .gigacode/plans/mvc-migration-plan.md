# MVC Migration Plan for Issue #191

## Current Status

### Pages Already Using MVC (9 pages):
1. b_building.php
2. overview.php
3. resources.php
4. buildings.php
5. notizen.php
6. techtree.php
7. techtreedetails.php
8. changelog.php
9. fleet_templates.php

### Pages That Need MVC Migration:
1. allianzen.php (alliances)
2. allianzdepot.php (alliance depot)
3. bewerben.php (apply)
4. bewerbungen.php (applications)
5. buddy.php (buddy system)
6. flotten1.php (fleet 1)
7. flotten2.php (fleet 2)
8. flotten3.php (fleet 3)
9. flottenversand.php (fleet dispatch - redirect page)
10. galaxy.php
11. imperium.php
12. infos.php
13. messages.php
14. micropayment.php
15. options.php
16. payment.php
17. pranger.php (external)
18. renameplanet.php
19. sprungtor.php (jumpgate)
20. statistics.php
21. suche.php (search)
22. trader.php
23. writemessages.php

### Special Pages (Bare/External):
1. bericht.php (bare - battle reports)
2. logout.php (bare - logout)
3. phalanx.php (bare - fleet monitoring)
4. ainfo.php (external - info page)

## Migration Strategy

### Phase 1: Simple Pages (Low Complexity)
Start with pages that have minimal GET/POST processing:
1. sprungtor.php
2. statistics.php
3. suche.php
4. renameplanet.php

### Phase 2: Medium Complexity Pages
Pages with some form processing:
1. options.php
2. messages.php
3. writemessages.php
4. allianzdepot.php
5. bewerben.php
6. bewerbungen.php
7. buddy.php
8. trader.php
9. micropayment.php
10. payment.php

### Phase 3: Complex Pages (High Complexity)
Pages with complex logic:
1. flotten1.php
2. flotten2.php
3. flotten3.php
4. flottenversand.php
5. galaxy.php
6. imperium.php
7. infos.php
8. allianzen.php

### Phase 4: Special Pages
1. bericht.php (bare page)
2. logout.php (bare page)
3. phalanx.php (bare page)
4. ainfo.php (external page)

## Implementation Steps

For each page:
1. Read current page implementation
2. Identify GET/POST parameter processing
3. Refactor to extend Page class with controller() and view() methods
4. Move all GET/POST processing to controller() method
5. Ensure view() method only renders output
6. Update router.json to add "mvc": true
7. Test thoroughly

## Testing Checklist

For each migrated page:
- [ ] GET requests work correctly
- [ ] POST requests work correctly
- [ ] Form submissions process correctly
- [ ] No stale data issues
- [ ] Redirects work correctly
- [ ] External pages work without session
- [ ] Bare pages skip headers/footers correctly
- [ ] All existing functionality preserved
