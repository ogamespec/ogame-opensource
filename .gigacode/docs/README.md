# OGame Memory Bank Index

## Overview
This memory bank contains comprehensive documentation for the OGame Open Source project. All documentation is organized by topic for easy reference.

## Documentation Structure

### 1. Project Overview
- **Project Summary** (`docs/project_summary.md`)
  - Project overview and statistics
  - Technology stack
  - Architecture analysis
  - Code quality assessment
  - Recommendations

### 2. Core Documentation
- **Database Schema** (`docs/database_schema.md`)
  - Complete database schema
  - Table descriptions
  - Helper functions
  - Configuration

- **Core Classes** (`docs/core_classes.md`)
  - Page class hierarchy
  - GameMod abstract class
  - bbcode class hierarchy
  - Database layer
  - Utility functions
  - Constants reference

- **Constants Reference** (`docs/constants_reference.md`)
  - Building IDs
  - Research IDs
  - Fleet IDs
  - Defense IDs
  - Resource IDs
  - Planet types
  - Fleet missions
  - Message types
  - Queue tasks
  - User flags
  - All game constants

### 3. Advanced Topics
- **Modification System** (`docs/modification_system.md`)
  - Mod structure
  - Hook methods reference
  - Installation process
  - Configuration
  - Best practices
  - Common examples

- **Battle Engine** (`docs/battle_engine.md`)
  - C engine architecture
  - PHP controller
  - Output format
  - Battle algorithm
  - Unit parameters
  - Rapidfire system
  - Testing

- **Testing Infrastructure** (`docs/testing_infrastructure.md`)
  - PHPUnit configuration
  - PHPStan configuration
  - Mock functions
  - Sample tests
  - Best practices
  - Troubleshooting

## Quick Reference

### Core Files
| File | Purpose |
|------|---------|
| `game/core/core.php` | Core module loader |
| `game/core/db.php` | Database layer |
| `game/core/defs.php` | Game definitions |
| `game/core/techs.php` | Technology definitions |
| `game/core/mods.php` | Modification system |
| `game/core/page.php` | Page rendering |
| `game/core/battle.php` | Battle controller |
| `game/core/battle_engine.php` | Battle engine interface |

### Configuration Files
| File | Purpose |
|------|---------|
| `composer.json` | PHP dependencies |
| `phpunit.xml` | PHPUnit configuration |
| `phpstan.neon` | PHPStan configuration |
| `docker-compose.yaml` | Docker setup |
| `Dockerfile` | Docker image |

### Documentation
| File | Purpose |
|------|---------|
| `.gigacode/docs/project_summary.md` | Project overview |
| `.gigacode/docs/database_schema.md` | Database documentation |
| `.gigacode/docs/core_classes.md` | Class documentation |
| `.gigacode/docs/constants_reference.md` | Constants reference |
| `.gigacode/docs/modification_system.md` | Mod documentation |
| `.gigacode/docs/battle_engine.md` | Battle engine docs |
| `.gigacode/docs/testing_infrastructure.md` | Testing docs |

## Getting Started

### For Developers
1. Read **Project Summary** for overview
2. Review **Database Schema** for data model
3. Study **Constants Reference** for game constants
4. Read **Modification System** for extending

### For Mod Developers
1. Read **Constants Reference** for IDs
2. Study **Modification System** for hooks
3. Review **Core Classes** for available methods
4. Check **Battle Engine** for combat logic

### For Testers
1. Review **Testing Infrastructure** for setup
2. Read **Core Classes** for test targets
3. Study **Constants Reference** for test data
4. Check **Battle Engine** for battle tests

### For New Users
1. Review **Project Summary** for project status
2. Check installation guides in `/wiki/en/`
3. Read README files for setup

## Memory Bank Organization

### By Topic
- **Database**: Schema, tables, queries
- **Classes**: Page, GameMod, bbcode
- **Constants**: All game constants
- **Mods**: Modification system
- **Battle**: Engine and algorithms
- **Testing**: Unit and integration tests

### By Functionality
- **Core**: Database, utilities, constants
- **Pages**: User and admin pages
- **Mods**: Modification hooks
- **Battle**: Combat system
- **Fleet**: Fleet management
- **Alliance**: Alliance system
- **Queue**: Event queue
- **Bot**: AI system

## Search Guide

### Finding Database Information
- Use **Database Schema** for table structure
- Check `game/core/db.php` for helper functions
- Look in `game/core/core.php` for table loading

### Finding Class Information
- Use **Core Classes** for class hierarchy
- Check `game/core/` for source files
- Review class documentation in code comments

### Finding Constant Values
- Use **Constants Reference** for all constants
- Check `game/core/defs.php` for definitions
- Review `game/core/techs.php` for tech IDs

### Finding Modification Hooks
- Use **Modification System** for hooks
- Check `game/core/mods.php` for GameMod
- Review mod examples in `game/mods/`

### Finding Battle Engine Info
- Use **Battle Engine** for algorithms
- Check `game/battle/battle.cpp` for C code
- Review `game/core/battle.php` for PHP interface

## Contribution Guidelines

### Adding Documentation
1. Follow existing documentation style
2. Use English language
3. Include code examples
4. Add cross-references
5. Test documentation accuracy

### Adding Constants
1. Define in `game/core/defs.php`
2. Document in `constants_reference.md`
3. Use consistent naming
4. Add usage examples

### Adding Classes
1. Document class hierarchy
2. Document all methods
3. Add usage examples
4. Document parameters and return types

## Maintenance

### Regular Updates
- Update documentation when code changes
- Add new constants as they're added
- Document new hooks for mods
- Update test documentation

### Quality Checks
- Verify code examples work
- Check cross-references
- Update outdated information
- Review documentation accuracy

## Troubleshooting

### Documentation Missing
1. Check **Project Summary** for overview
2. Review related documentation
3. Search source code for comments
4. Ask in Discord community

### Information Outdated
1. Check last modification date
2. Verify against source code
3. Report issues
4. Update documentation

### Code Not Working
1. Check version compatibility
2. Review documentation
3. Test with sample code
4. Check error logs

## Additional Resources

### Community
- Discord: https://discord.gg/xpCV3McAj2
- GitHub: https://github.com/ogamespec/ogame-opensource

### Documentation
- Wiki: `/wiki/en/`
- README: `README.md`, `ReadmeRus.md`
- Installation: `/wiki/en/install.md`

### Tools
- PHPStan: Static analysis
- PHPUnit: Unit testing
- Docker: Containerization

## Version History

### v1.0.0
- Initial memory bank documentation
- Database schema complete
- Core classes documented
- Modification system documented
- Battle engine documented
- Testing infrastructure documented

---

**Last Updated**: 2026-06-25
**Version**: 1.0.0.0
**Status**: Complete
