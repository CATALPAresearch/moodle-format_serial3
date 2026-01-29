# Moodle Plugin Code Quality Requirements for Official Plugin Directory

## Code Standards and Style

### PHP Coding Style
- Follow **Moodle coding style** strictly: https://moodledev.io/general/development/policies/codingstyle
- Use **4 spaces** for indentation (no tabs)
- Opening braces on same line: `if ($condition) {`
- One statement per line
- Maximum line length: 132 characters (prefer 80-100)
- Use `elseif` not `else if`
- Space after keywords: `if (`, `foreach (`, `function (`
- No space before semicolons
- Always use braces for control structures (even single lines)

### Naming Conventions
- Class names: `PascalCase` (e.g., `class CourseFormatHelper`)
- Function names: `lowercase_with_underscores` (e.g., `get_section_name()`)
- Variable names: `$lowercasewithunderscores`
- Constants: `UPPERCASE_WITH_UNDERSCORES`
- Database tables: `{prefix}_yourplugin_tablename`
- Capabilities: `format/yourformat:capabilityname`
- Language strings: lowercase with underscores

### File Headers
Every PHP file must have:
```php
<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Brief description of file purpose.
 *
 * Detailed description if needed.
 *
 * @package    format_yourformat
 * @copyright  2024 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
```

### PHPDoc Comments
- Required for **all** classes, functions, and class properties
- Use proper PHPDoc tags:
```php
/**
 * Short description (one line).
 *
 * Longer description if needed.
 *
 * @param int $courseid Course ID
 * @param stdClass $section Section object
 * @return string HTML output
 * @throws moodle_exception When course not found
 */
function render_section($courseid, $section) {
    // Implementation
}
```

## Security Requirements

### Input Validation
- **Never trust user input**
- Use `required_param()`, `optional_param()` for form data
- Use proper PARAM_* types: `PARAM_INT`, `PARAM_TEXT`, `PARAM_ALPHANUMEXT`, etc.
- Validate all data from database before use

### Output Escaping
- Use `s()` for plain text output: `echo s($user->name);`
- Use `format_string()` for strings with multi-lang support
- Use `format_text()` for HTML content (respects filters)
- Never use `echo` with unescaped user data

### SQL Injection Prevention
- **Never** use string concatenation for SQL
- Use placeholders: `$DB->get_records('table', ['field' => $value])`
- Use `$DB` API methods (never raw SQL unless absolutely necessary)
```php
// Good
$records = $DB->get_records_sql('SELECT * FROM {table} WHERE id = ?', [$id]);

// Bad
$records = $DB->get_records_sql("SELECT * FROM {table} WHERE id = $id");
```

### XSS Prevention
- Escape all output
- Use `{{{ }}}` in Mustache templates for escaped output
- Use `{{{ }}}` not `{{ }}` unless HTML is intentional and sanitized
- Sanitize HTML with `clean_text()` or `purify_html()`

### CSRF Protection
- Use `sesskey` for all forms: `<input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">`
- Validate with `require_sesskey()` or `confirm_sesskey()`

### Capability Checks
- Always check permissions: `require_capability('format/yourformat:view', $context);`
- Use appropriate context (course, module, system)
- Never assume user has access

## Database Usage

### Best Practices
- Use `$DB` global object (Data Manipulation API)
- Use table prefixes: `{tablename}` in SQL
- Use transactions for multiple related queries:
```php
$transaction = $DB->start_delegated_transaction();
try {
    $DB->insert_record('table1', $data1);
    $DB->update_record('table2', $data2);
    $transaction->allow_commit();
} catch (Exception $e) {
    $transaction->rollback($e);
}
```

### Performance
- Use `get_records()` instead of loops with `get_record()`
- Use `get_fieldset_sql()` for single column
- Add database indexes in install.xml
- Avoid N+1 query problems

## JavaScript Requirements

### AMD Modules
- Use AMD format (required for Moodle 3.x+)
- File location: `amd/src/yourmodule.js`
- Run `grunt amd` to build
```javascript
define(['jquery', 'core/notification'], function($, Notification) {
    return {
        init: function() {
            // Your code
        }
    };
});
```

### ES6 Modules (Moodle 4.0+)
- Can use ES6 syntax in `amd/src/`
- Grunt will transpile to AMD
- Use proper imports/exports

### Code Quality
- Pass ESLint validation
- No console.log in production code
- Use Promises or async/await for async operations
- Handle errors properly
- Use Moodle's AJAX API, not raw jQuery.ajax()

### Grunt Validation
Run before submission:
```bash
grunt amd
grunt eslint:amd
```

## Accessibility (WCAG 2.1 AA)

### Requirements
- Semantic HTML5 elements
- Proper heading hierarchy (h1, h2, h3...)
- Alt text for all images
- ARIA labels where needed
- Keyboard navigation support
- Focus indicators visible
- Color contrast ratio ≥ 4.5:1 for normal text
- Form labels properly associated
- No reliance on color alone for information

### Testing
- Test with keyboard only (Tab, Enter, Space, Arrow keys)
- Test with screen reader (NVDA, JAWS, VoiceOver)
- Run automated tools (axe, WAVE)

## Internationalization (i18n)

### Language Strings
- All UI text in language files: `lang/en/format_yourformat.php`
- Never hardcode English strings in PHP/JS
- Use `get_string('identifier', 'format_yourformat')` in PHP
- Use `str.get_string()` in JavaScript
```php
// lang/en/format_yourformat.php
$string['pluginname'] = 'Your Format';
$string['sectionname'] = 'Section';

// In code
echo get_string('sectionname', 'format_yourformat');
```

### String Guidelines
- Use placeholders: `$string['greeting'] = 'Hello, {$a}';`
- For multiple placeholders: `$string['info'] = 'User {$a->name} in course {$a->course}';`
- Avoid concatenating strings
- Support RTL languages (use logical properties in CSS)

## Privacy API (GDPR Compliance)

### Required Implementation
Must implement `\core_privacy\local\metadata\provider` interface:

```php
namespace format_yourformat\privacy;

class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider {
    
    public static function get_metadata(collection $collection): collection {
        // Declare what data you store
        $collection->add_database_table(
            'format_yourformat_settings',
            [
                'userid' => 'privacy:metadata:userid',
                'preference' => 'privacy:metadata:preference',
            ],
            'privacy:metadata:tablepurpose'
        );
        return $collection;
    }
    
    public static function export_user_data(approved_contextlist $contextlist) {
        // Export user's data
    }
    
    public static function delete_data_for_all_users_in_context(\context $context) {
        // Delete all user data in context
    }
    
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        // Delete specific user's data
    }
}
```

## Testing Requirements

### PHPUnit Tests
- Test all public methods
- Test edge cases and error conditions
- Use data providers for multiple test cases
- Location: `tests/yourtest_test.php`
```php
namespace format_yourformat;

class yourtest_test extends \advanced_testcase {
    public function test_something() {
        $this->resetAfterTest();
        // Test code
        $this->assertEquals($expected, $actual);
    }
}
```

### Behat Tests (Acceptance Tests)
- Test user workflows
- Location: `tests/behat/yourfeature.feature`
- Use Gherkin syntax
```gherkin
@format @format_yourformat
Feature: Display course sections
  In order to view course content
  As a student
  I need to see sections in the format

  Scenario: View sections as student
    Given the following "courses" exist:
      | fullname | shortname | format      |
      | Course 1 | C1        | yourformat  |
    When I log in as "student1"
    Then I should see "Section 1"
```

### Code Coverage
- Aim for >80% code coverage
- Run: `vendor/bin/phpunit --coverage-html coverage/`

## Code Analysis Tools

### Moodle Code Checker
**Required** - must pass with no errors:
```bash
composer require moodlehq/moodle-cs
vendor/bin/phpcs --standard=moodle /path/to/your/plugin
```

Fix automatically where possible:
```bash
vendor/bin/phpcbf --standard=moodle /path/to/your/plugin
```

### Moodle PHPDoc Checker
```bash
composer require moodlehq/moodle-local_moodlecheck
# Run through Moodle admin interface
```

### PHP Compatibility
- Support PHP versions as per Moodle requirements
- Currently: PHP 7.4, 8.0, 8.1, 8.2
- Use PHP_CodeSniffer with PHPCompatibility ruleset

## Performance Standards

### Efficiency
- Cache expensive operations using Moodle Caching API (MUC)
- Minimize database queries
- Use lazy loading for heavy content
- Optimize images (compress, use appropriate formats)
- Minify CSS/JS (via Grunt)

### Caching Example
```php
$cache = cache::make('format_yourformat', 'mycache');
$data = $cache->get('key');
if ($data === false) {
    $data = expensive_operation();
    $cache->set('key', $data);
}
```

## Documentation Requirements

### README.md
- Installation instructions
- Configuration guide
- Features overview
- Screenshots
- Credits/acknowledgments

### CHANGES.md
- Version history
- Breaking changes highlighted
- Migration guides for major versions

### Code Comments
- Complex logic explained
- Non-obvious decisions documented
- TODOs removed before submission

### Wiki/Online Docs
- User guide
- Developer documentation
- FAQ

## Version Control

### Git Best Practices
- Clean commit history
- Descriptive commit messages
- No merge commits (rebase workflow preferred)
- Tag releases: `v1.0.0`, `v1.1.0`
- Follow semantic versioning

### .gitignore
Include:
```
/amd/build/
/node_modules/
/.grunt/
/.php_cs.cache
/coverage/
```

## Licensing

### GPL v3+
- All files must be GPL v3 or later
- Include license header in every file
- Include `LICENSE.txt` or `COPYING.txt` in root
- No proprietary dependencies

### Third-party Code
- Document all third-party libraries
- Ensure compatible licenses
- Include third-party licenses in `thirdpartylibs.xml`

## Plugin Validation Checklist

Before submission to plugins directory:

- [ ] Passes `moodle-cs` (Code Checker) with 0 errors
- [ ] Passes `moodle-local_moodlecheck` (PHPDoc Checker)
- [ ] All strings in language files (no hardcoded text)
- [ ] PHPUnit tests written and passing
- [ ] Behat tests for key features
- [ ] Privacy API implemented
- [ ] GPL v3+ license headers on all files
- [ ] No security vulnerabilities (XSS, SQL injection, CSRF)
- [ ] Works on supported Moodle versions (test on minimum version)
- [ ] Works on supported PHP versions
- [ ] Accessible (WCAG 2.1 AA)
- [ ] Works on mobile (responsive)
- [ ] Works with major themes (Boost, Classic)
- [ ] No JavaScript errors in console
- [ ] No PHP warnings/notices
- [ ] Database tables properly defined in install.xml
- [ ] Upgrade script works correctly
- [ ] Backup/restore supported
- [ ] Documentation complete (README, inline comments)
- [ ] Version number and dependencies correct in version.php

## CI/CD Integration

### GitHub Actions
Set up automated testing:
```yaml
name: Moodle Plugin CI
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup Moodle
        uses: moodlehq/moodle-plugin-ci@v3
      - name: Run tests
        run: moodle-plugin-ci run
```

## Submission Process

### Plugin Directory Requirements
1. Register on https://moodle.org/plugins
2. Submit plugin for approval
3. Provide:
   - Plugin name and description
   - Supported Moodle versions
   - Screenshots
   - Documentation links
   - Source code repository URL

### Review Process
- Automated validation runs first
- Manual code review by Moodle HQ
- Feedback provided if changes needed
- Approval can take 1-4 weeks

### Maintenance
- Respond to bug reports promptly
- Update for new Moodle versions
- Maintain backwards compatibility
- Communicate breaking changes clearly

## Resources

- **Moodle Developer Docs**: https://moodledev.io
- **Coding Style**: https://moodledev.io/general/development/policies/codingstyle
- **Security Guidelines**: https://moodledev.io/general/development/policies/security
- **Plugin Directory**: https://moodle.org/plugins
- **Moodle Tracker**: https://tracker.moodle.org

---

## Quick Reference for AI Coding Assistants

**Key Points for Copilot/AI:**
- "Follow Moodle coding standards strictly (4 spaces, braces on same line, lowercase_underscore functions)"
- "Include GPL v3+ license header in all files"
- "Use $DB API for all database operations with placeholders"
- "Escape all output with s(), format_string(), or format_text()"
- "Add PHPDoc comments to all functions and classes"
- "Use language strings for all UI text"
- "Implement Privacy API for GDPR compliance"
- "Make code accessible (WCAG 2.1 AA)"
- "Pass moodle-cs code checker"
- "Use sesskey for CSRF protection"
- "Check capabilities before any privileged operation"
- "Never hardcode strings - use lang files"
- "Support multiple Moodle and PHP versions"