# Contributing

Contributions are **welcome** via Pull Requests on [Github](https://github.com/Jeroen-G/Packager).

An interesting read is [Contributing to a Github Project](http://jasonlewis.me/article/contributing-to-a-github-project).

Also try to code in the same style as Laravel (which followes the [PSR standard](http://www.php-fig.org/) guidelines).
The code style is checked with Easy Coding Standard in GitHub Actions; run `make codestyle-fix` to fix it locally.

Before sending a pull request, run:
- `make test` for the unit and integration tests (with coverage),
- `make phpstan` for static analysis,
- `make infection` for mutation testing,
- `make codestyle` for the code style.

## Things To Do
If you want to contribute but do not know where to start, this list provides some starting points.
- Raise the mutation score and the minimum MSI in CI.

## Pull Requests

- **Add tests!** - Your patch likely won't be accepted if it doesn't have tests.

- **Document any change in behaviour** - Make sure the `readme.md`, `changlog.md` and any other relevant documentation are kept up-to-date.

- **Consider our release cycle** - We try to follow [SemVer v2.0.0](http://semver.org/). Randomly breaking public APIs is not an option.

- **One pull request per feature** - If you want to do more than one thing, send multiple pull requests.

- **Send coherent history** - Make sure each individual commit in your pull request is meaningful. If you had to make multiple intermediate commits while developing, please [squash them](http://www.git-scm.com/book/en/v2/Git-Tools-Rewriting-History#Changing-Multiple-Commit-Messages) before submitting.


**Happy coding**!
