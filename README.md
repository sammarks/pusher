# Pusher

Commit and deploy with ease! Using one command, your commits
are automatically deployed to your selected server through
SSH sessions. No more worrying about SSH sessions expiring
or wondering if your changes were actually deployed!

## Installation

To install pusher, simply download and install the repository
to your `/usr/local` folder.

    sudo su
    git clone https://github.com/sammarks/pusher /usr/local/pusher
    chmod a+x /usr/local/pusher/pusher
    cd /usr/local/pusher
    sudo composer install

Then add pusher to your `PATH`.

    PATH="/usr/local/pusher:$PATH"

Now, restart your shell and run Pusher by typing:

    pusher

from anywhere!

## Usage

Write some code, and then run this command from the root directory
of your project:

    pusher submit "Commit Message"

You'll then be asked to setup your project. The project setup wizard
will ask you several questions about your project configuration. Here
are a couple of the key configuration values:

- Directory of the project
- VCS Type (subversion, git)
- Remote Host
- Remote Directory
- `sudo` needed on remote
- Custom commands to run on remote
