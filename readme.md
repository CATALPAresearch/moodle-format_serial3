# SERIAL 3 - SElf Regulation induced by Adaptive Learning, version 3

Serial 3 is a _Learning Analytics Dashboard_ for Moodle courses. The dashboard consists of several widgets
that can be selected and arranged to the users liking.

**Features**

- Customizable widget-based dashboard system
- Implemented widgets
   - Progress Chart with adaptive learning support
   - Learner goals and Indicators
   - Recommendations
   - Task List
   - Deadlines
   - Quiz Statistics

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

**Widget development:** See [readme-widget-setup.md](readme-widget-setup.md)

**Moodle compliance:** See [readme-moodle-compliance.md](readme-moodle-compliance.md)

## Contributors

- Niels Seidel
- Marc Burchart
- Heike Karolyi
- Valerie Meyer
- Slavisa Radovic
