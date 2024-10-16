ColorOff='\[\e[00m\]'
BRed='\[\e[01;31m\]'
BBlue='\[\e[01;34m\]'
PS1="${ColorOff}${BRed}\u@\h${ColorOff}:${BBlue}\w${ColorOff} $ "

HISTFILE="/home/retailcrm/bash/bash_history"

cd /var/www/crm/