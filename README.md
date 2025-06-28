🚀 Project Auto Creator Dashboard

This PHP-based automation tool is designed to manage your GitHub repositories automatically through a web-based dashboard. Originally, it was used to create new private repositories on a schedule, but it has now been adapted to edit the README file of an existing repository instead.

Key Features:

Connects directly to your GitHub account using a secure personal access token

Shows connection status in a friendly interface

Logs all operations to a run.log file

Allows you to trigger the README update with a single button

Commits the provided README content automatically, without creating new projects each time

Provides a clean, modern dashboard to monitor activities

How it works now (updated):

Instead of creating a new repository every time, the tool edits the existing repository’s README

Commits the updated README using the GitHub API

Logs the commit status in run.log

Keeps all changes centralized in a single repository

This approach avoids clutter from repeatedly making new repos, while still letting you manage fresh README content as needed.

Perfect for:

auto-updating documentation

testing CI/CD workflows

or keeping a single private repo with automated content changes

(Built with ❤️ by Utkarsh, powered by PHP + GitHub REST API)
