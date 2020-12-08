# Run .travis.yml Github Action

![C/I Status](https://github.com/ktomk/run-travis-yml/workflows/C/I/badge.svg)

For the pipelines project there was need to migrate from travis-ci.org
to travis-ci.com (as travis-ci.org is shutting down).

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

## Hints
* Lightweight port to support migrating travis-ci build scripts, your
  mileage may vary.
* Running the stage(s) as a build script based on the original travis-build
* Custom stages only (no matrix, deployment, after_success etc.)
* Environment variables are likely incomplete, add missing ones your own
* Github runner is missing timing information, folding works but is incomplete
* Github has no allow-failure option when running actions. That
  means the first failing build (action) cancels all other workflow actions
  as well
* Path to file can be specified `with:' 'file:` (optional)
* Stages (and their order) can be specified `with:` `stages:` as a space
  separated list (optional, by default all custom stages are run)

## License
See [COPYING](COPYING), for parts from *travis-build* see
[LICENSE](LICENSE) and see other [LICENSE] for Symfony YAML.

## Resources
* [travis-ci/travis-build](https://github.com/travis-ci/travis-build) - .travis.yml => build.sh converter
* [travis-ci/dpl](https://github.com/travis-ci/dpl) - Dpl (dee-pee-ell) is a deploy tool made for continuous deployment
* [JoshCheek/travis-environment](https://github.com/JoshCheek/travis-environment) - A repo to reflect on the Travis CI environment
* [ktomk/pipelines](https://github.com/ktomk/pipelines) - Command line pipeline runner written in PHP

---
[LICENSE]: lib/ktomk/symfony-yaml/Symfony/Component/Yaml/LICENSE
