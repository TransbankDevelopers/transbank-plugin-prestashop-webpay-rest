#!/bin/bash

git config commit.gpgsign false
git config tag.gpgsign false
git config --unset-all user.signingkey || true
git config --unset gpg.format || true
git config --unset-all gpg.program || true

git config --global commit.gpgsign false
git config --global tag.gpgsign false
git config --global --unset-all gpg.program || true
git config --global --unset user.signingkey || true
git config --global gpg.format openpgp
