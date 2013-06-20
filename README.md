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

Then add pusher to your `PATH`.

    PATH="/usr/local/pusher:$PATH"

Now, restart your shell and run Pusher by typing:

    pusher

from anywhere!
