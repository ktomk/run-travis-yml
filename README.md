# Run .travis.yml Github Action

[![CI Status][badge.svg]](https://github.com/ktomk/run-travis-yml/actions)

For the [pipelines project][p] there was need to migrate from travis-ci.org
to travis-ci.com (as travis-ci.org is shutting down).

> **Pro-Tip:** Do not migrate from travis-ci.org to travis-ci.com, but
> look for other migration options first.

Turned out it is good to have some more options to execute the `.travis.yml`
based build script as well on Github via Github Actions.

Extracting parts of the open-source interface of Travis-CI from
[`travis-build`][TRAVIS-BUILD] as a *Github Action* to make the
`.travis.yml` more portable to Github w/o changing everything at once
and a smoother migration.

Packaged as a *Github Action* for everyone it may be useful.

## Usage

```yaml
  - name: Run .travis.yml build script
    uses: ktomk/run-travis-yml@v1
    with:
      file: .travis.yml
      stages: |
        install
        script
    env:
      TRAVIS_PHP_VERSION: ${{ matrix.php-versions }}
```

* (*optional*) **Path to `.travis.yml` file** can be specified `with:` `file:`
  (by default `.travis.yml`).
* (*optional*) **Stages to run** can be specified `with:` `stages:` as a space
  separated list (by default [all custom stages][acs] are run).
* **Environment variables** are likely incomplete (some are ported), add
  missing ones or override your own, the `env:` is key.

## Notes
* Lightweight port to support migrating travis-ci build scripts, your
  mileage may vary.
* Running the stage(s) as build script based on the original
  [`travis-build`][TRAVIS-BUILD].
* Custom stages only (no matrix, deployment, after_success etc.), this needs
  additional matrix/actions in your workflow (checkout, VM setup, services,
  caching).
* The runner on Github does not have the timing information as nice as the
  one on Travis-CI. Folding works but hides the first line of the command
  when collapsed (the display on Travis CI is generally looking better to
  me, also while the action is running, Github truncates log output).
* Github has no allow-failure option when running actions. That
  means the first failing build (action) cancels the overall workflow.
  [`continue-on-error:`][coe] may help, see
  [actions/toolkit#399][at-399].

## Copying
`AGPL-3.0-or-later` see [COPYING], `MIT` for files from *travis-build* see
[TRAVIS-LICENSE] and `MIT` for files from *Symfony YAML* see [LICENSE].

## Resources
* [travis-ci/travis-build][TRAVIS-BUILD] - .travis.yml => build.sh converter
* [travis-ci/dpl](https://github.com/travis-ci/dpl) - Dpl (dee-pee-ell) is
  a deploy tool made for continuous deployment
* [JoshCheek/travis-environment](https://github.com/JoshCheek/travis-environment
  ) - A repo to reflect on the Travis CI environment
* [Travis to GitHub Actions converter](https://akx.github.io/travis-to-github-actions/
  ) - SPA to convert Travis.yml workflows to GitHub Actions; Python and
  Node.js workflows, contributions welcome; by Aarni Koskela; Dec 2020
* [Migrating From Travis to GitHub Actions](https://markphelps.me/2019/09/migrating-from-travis-to-github-actions/)
  by Mark Phelps; Sep 2019
* [Migrate From Travis CI to GitHub Actions](https://developer.okta.com/blog/2020/05/18/travis-ci-to-github-actions)
  by Brian Demers for Okta; May 2020
* [ktomk/pipelines](https://github.com/ktomk/pipelines) - Command line
  pipeline runner written in PHP

---
[COPYING]: COPYING
[LICENSE]: lib/ktomk/symfony-yaml/Symfony/Component/Yaml/LICENSE
[TRAVIS-LICENSE]: lib/template/TRAVIS-LICENSE
[TRAVIS-BUILD]: https://github.com/travis-ci/travis-build
[acs]: https://github.com/travis-ci/travis-build/blob/master/lib/travis/build/stages.rb#L12-L65
[at-399]: https://github.com/actions/toolkit/issues/399
[badge.svg]: https://github.com/ktomk/run-travis-yml/workflows/CI/badge.svg
[coe]: https://docs.github.com/en/free-pro-team@latest/actions/reference/workflow-syntax-for-github-actions#jobsjob_idcontinue-on-error
[p]: https://github.com/ktomk/pipelines
