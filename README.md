# OGame Open Source

> [!WARNING]
> We are currently undergoing a massive refactoring based on PHPStan analysis results, and a [modification engine](/wiki/en/mods.md) is being added. Therefore, some bugs and syntax errors may be present, but they will be fixed as we go.

This is a revived OGame v 0.84 with the old design.

---

## Table of contents

- [About](#about)
- [Installation](#installation)
- [Features](#features)
- [Attribution](#attribution)
- [Legal](#legal)

---

## About

**OGame Open Source** is a faithful recreation of the classic OGame 0.84 with the original look and feel. All gameplay mechanics, resource costs, production timings and the home-planet distribution algorithm are preserved.

> :warning: **Fellow developers!** Don't be confused by the amount of files in the repository root. You probably don't need most of it — those are spare parts for Docker, PHPStan and PHPUnit. The main source files live in the `game` folder.

![whc50b7bd1f6b2a2](/wiki/imgstore/whc50b7bd1f6b2a2.jpg)

---

## Installation

Need help setting up the game? You have the following options:

- Use the **millennial** guide: [install](/wiki/en/install.md)
- Use the **zoomer** guide: [install_docker](/wiki/en/install_docker.md)
- There is also another Docker deployment option by Noli: <https://gitlab.com/nolialsea/ogame-opensource-docker>
- Ask the community for help on **Discord**: <https://discord.gg/xpCV3McAj2>

---

## Features

- **Original game mechanics**
- Well-tested fast battle engine with fair rapid-fire, written in C (with a PHP backup)
- Improved admin tool
- Integrated Galaxy-tool
- CRON-less event queue (with an optional CRON mode)
- Multi-language support
- ACS
- Planet temperature, images and sizes identical to the original game
- 100% match on resource costs and production timings
- Original home-planet distribution algorithm and spy protection
- Fixed several original game bugs (buggy 10th planets, recalled ACS delay, buggy fleet return activity, etc.)
- Original expedition with triple Dark Matter chance
- Open source!
- …and many more!

### Languages

Currently only **Russian**, **English** and **German** are supported. Other language packs can be contributed by volunteers — the game engine is fully multilingual.

---

## Attribution

Credits go to **Alexander Rösner (Legor)** for such a revolutionary breakthrough in browser games. He was not the first, but he was the one who succeeded.

To pay respect, we still keep Legor's account, sitting on his own planet Arakis at **[1:1:2]** =)

---

## Legal

**This is a non-commercial project. All Premium functions of the original OGame (Dark Matter, Officers and the Trader) are free.**

All copyrighted material is proprietary Gameforge stuff. We do not make money on it — we just have fun.

**!!! All trademarks and copyrighted materials belong to their respective owners — OGame © Gameforge 4D GmbH !!!**
