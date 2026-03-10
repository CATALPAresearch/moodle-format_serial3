# SERIAL 3 - SElf Regulation induced by Adaptive Learning, version 3

Serial 3 is a _Learning Analytics Dashboard_ for Moodle courses. The dashboard provides several widgets that the teacher can enable. From this set of widgets students can choose their widgets and arrange and configure them in a dashboard.

**Features**

- Customizable widget-based dashboard system
- Implemented widgets
  - Progress chart with adaptive learning support
  - Learner goals and indicators
  - Recommendations
  - Task list
  - Deadlines:
  - Teacher Activity: Visualizes
  - Quiz statistics

**Roadmap**

- Add the resource list
- Add a time tracking tool and display

# Installation

1. `git clone` the repository to /your-moodle/course/format/
2. Open the page https://<moodle>/admin/index.php?cache=1 and follow the install instructions for the plugin.
3. Open a course of you choice and go to the _course settings_ (watch out for the little cog-icon). Set the 'course
   format' to 'Serial 3'.

```bash
# push code to test system
rsync -r ./* aple-test:/var/moodle/htdocs/moodle/course/format/serial3 --exclude={'.env','node_modules','*.git','.DS_Store','.gitignore','.vscode'}

```

# Development

- run `npm install` to install all dependencies
- change to folder `vue` and run in terminal `npm run build` to transpile changes from in vue to js

**Dependencies**

- Moodle v4.5 or newer
- vue.js v3, vuex v4
- d3.js
- vue-grid-layout library (https://jbaysolutions.github.io/vue-grid-layout/)

## Testing

```bash
cd /path/to/moodle
php admin/tool/phpunit/cli/init.php
# Run all tests (72)
find course/format/serial3/tests -name '*_test.php' -exec vendor/bin/phpunit {} \;
# Or run specific test file
vendor/bin/phpunit course/format/serial3/tests/webservices_test.php
```

## Getting Started

**Key files:**

- `ws/*.php` - Webservice implementations (strict naming conventions required)
- `db/services.php` - Webservice definitions referencing ws classes
- `vue/` - Vue.js application source (transpiles to `amd/`)
- `version.php` - Increment when changing services.php

**Widget development:**
Widgets are elements containing data visualisiations or learning support instruments that can be arranged together in a dashboard.  
Teachers can enable/disable dashboard widgets via a modal interface (slider icon in MenuBar). From the set of enables widgets students can choose which widgets they want to see at certain position on the dashboard.

See how to add a widget to the dashboard [readme-widget-setup.md](readme-widget-setup.md)

**Moodle compliance:** See [readme-moodle-compliance.md](readme-moodle-compliance.md)

## Credits

This software uses the following open source packages:
[vue.js](https://vuejs.org/),
[vuex](https://vuex.vuejs.org/),
[vue-router](https://router.vuejs.org/),

## Related Moodle Plugins

- tba

## Citation

**Cite this software:**

```bibtex
@misc{Seidel2024-MoodleSerial3,
  title = {{{SERIAL3}} - {{A}} Course Format Plugin Supporting Adaptive Self-Regulated Learning in {{Moodle}}},
  author = {Seidel, Niels and Meyer, Valerie},
  date = {2024},
  doi = {10.17605/OSF.IO/9TKPS},
  url = {https://github.com/CATALPAresearch/format_serial3}
}
```

## Research articles and datasets about Longpage

**Peer-reviewed papers**

## You may also like ...

- [mod_longpage](https//github.com/catalparesearch/mod_longpage) - Supporting reading of long texts.
- [mod_usenet](https//github.com/catalparesearch/mod_usenet) - Usenet client for Moodle
- [local_ari](https//github.com/catalparesearch/local_ari) - Adaptation Rule Interface
- [mod_openchat](https://github.com/nise/mod_openchat) - LLM and agent chat plugin

## Contributors

- Niels Seidel (project lead)
- Marc Burchart
- Heike Karolyi
- Valerie Meyer
- Slavisa Radovic

## Licence

[GNU GPL v3 or later](http://www.gnu.org/copyleft/gpl.html)

---

<a href="https://www.fernuni-hagen.de/english/research/clusters/catalpa/"><img src="pix/catalpa.jpg" width="300" /></a>
<a href="https://www.fernuni-hagen.de/"><img src="pix/fernuni.jpg" width="250" /></a>
