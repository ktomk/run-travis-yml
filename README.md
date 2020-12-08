# Run .travis.yml Github Action

![C/I Status](https://github.com/ktomk/run-travis-yml/workflows/C/I/badge.svg)

For the [pipelines project][p] there was need to migrate from travis-ci.org
to travis-ci.com (as travis-ci.org is shutting down).

> **Pro-Tip:** Do not migrate from travis-ci.org to travis-ci.com, but
> look for other migration options first.

Turned out it is good to have some more options to execute the `.travis.yml`
based build scripts as well on Github via Github Actions.

```yaml
  # Run Travis-CI build scripts on Github
  - uses: ktomk/run-travis-yml@v1
    with:
      file: .travis.yml
      stages: install script
    env:
      TRAVIS_PHP_VERSION: ${{ matrix.php-versions }}
```

Extracting part of the open-source interface of Travis-CI that is in
use in the pipelines project to be more easily portable to Github
w/o changing everything at once and to smoothly migrate.

Packaged as a Github Action for everyone it may be useful.

## Hints
* Lightweight port to support migrating travis-ci build scripts, your
  mileage may vary.
* Running the stage(s) as a build script based on the original travis-build
* Custom stages only (no matrix, deployment, after_success etc.)
* Environment variables are likely incomplete, add missing ones your own
* Github runner is missing timing information, folding works but is
  incomplete (the display on Travis CI is generally looks better to me,
  also while the build is runnning)
* Github has no allow-failure option when running actions. That
  means the first failing build (action) cancels all other workflow actions
  as well
* Path to file can be specified `with:` `file:` (optional, relative to
  your projects root)
* Stages (and their order) can be specified `with:` `stages:` as a space
  separated list (optional, by default [all custom stages][acs] are run)

## License
See [COPYING](COPYING), for parts from *travis-build* see
[LICENSE](LICENSE) and see other [LICENSE] for Symfony YAML.

## Resources
* [travis-ci/travis-build](https://github.com/travis-ci/travis-build) -
  .travis.yml => build.sh converter
* [travis-ci/dpl](https://github.com/travis-ci/dpl) - Dpl (dee-pee-ell) is
  a deploy tool made for continuous deployment
* [JoshCheek/travis-environment](https://github.com/JoshCheek/travis-environment)
  - A repo to reflect on the Travis CI environment
* [Migrate From Travis CI to GitHub Actions](https://developer.okta.com/blog/2020/05/18/travis-ci-to-github-actions)
  by Brian Demers for Okta; May 2020
* [ktomk/pipelines](https://github.com/ktomk/pipelines) - Command line
  pipeline runner written in PHP

---
[LICENSE]: lib/ktomk/symfony-yaml/Symfony/Component/Yaml/LICENSE
[acs]: https://github.com/travis-ci/travis-build/blob/master/lib/travis/build/stages.rb#L12-L65
[p]: https://github.com/ktomk/pipelines
