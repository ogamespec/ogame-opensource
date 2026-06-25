# MVC Migration Plan for Issue #191

## Current Status

### Pages Already Using MVC (6 pages):
1. b_building.php
2. overview.php
3. resources.php
4. buildings.php
5. notizen.php
6. techtree.php
7. techtreedetails.php

### Pages That Need MVC Migration:
1. allianzen.php (alliances)
2. allianzdepot.php (alliance depot)
3. bewerben.php (apply)
4. bewerbungen.php (applications)
5. buddy.php (buddy system)
6. changelog.php
7. fleet_templates.php
8. flotten1.php (fleet 1)
9. flotten2.php (fleet 2)
10. flotten3.php (fleet 3)
11. flottenversand.php (fleet dispatch - redirect page)
12. galaxy.php
13. imperium.php
14. infos.php
15. messages.php
16. micropayment.php
17. options.php
18. payment.php
19. pranger.php (external)
20. renameplanet.php
21. sprungtor.php (jumpgate)
22. statistics.php
23. suche.php (search)
24. trader.php
25. writemessages.php

### Special Pages (Bare/External):
1. bericht.php (bare - battle reports)
2. logout.php (bare - logout)
3. phalanx.php (bare - fleet monitoring)
4. ainfo.php (external - info page)

## Migration Strategy

### Phase 1: Simple Pages (Low Complexity)
Start with pages that have minimal GET/POST processing:
1. changelog.php
2. fleet_templates.php
3. sprungtor.php
4. statistics.php
5. suche.php
6. renameplanet.php

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
