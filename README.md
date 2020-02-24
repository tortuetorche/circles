# Nextcloud Circles

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/nextcloud/circles/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/nextcloud/circles/?branch=master)
[![codecov](https://codecov.io/gh/nextcloud/circles/branch/master/graph/badge.svg)](https://codecov.io/gh/nextcloud/circles)
[![Build Status](https://drone.nextcloud.com/api/badges/nextcloud/circles/status.svg)](https://drone.nextcloud.com/nextcloud/circles)

**Bring cloud-users closer together.**

![](https://raw.githubusercontent.com/nextcloud/circles/master/screenshots/0.12.0.png)

Circles allows your users to create their own groups of users/colleagues/friends. 
Those groups of users (or circles) can then be used by any other app for sharing purpose 
(files, social feed, status update, messaging, ...) through the Circles API

Different types of circles can be created:


- A **Personal Circle** is a list of users known only to the owner.  
This is the right option if you want to do recurrent sharing with the same list of local users.

- A **Public Circle** is an open group visible to anyone willing to join.  
Anyone can see the circle, can join the circle and access the items shared to the circle.
 
- Joining a **Closed Circle** requires an invitation or a confirmation by a moderator.  
Anyone can find the circle and request an invitation; but only members will see who's in it and get access to shared items.

- A **Secret Circle** is an hidden group that can only be seen by its members or by people knowing the exact name of the circle.  
Non-members won't be able to find your secret circle using the search bar.


***
# API (PHP & Javascript)

[Please visit our wiki to read more about the API.](https://github.com/nextcloud/circles/wiki)

# Configuration

## Allow usage of Circles in non-SSL environments

In non-SSL environments (like on development setups) it is necessary to set two config flags for Circles:

`./occ config:app:set circles --value 1 allow_non_ssl_links`

`./occ config:app:set circles --value 1 local_is_non_ssl`

## Allow mirroring circles as groups

See: https://github.com/nextcloud/circles/issues/363

```bash
apk add --no-cache --virtual .maintenance-deps sudo

sudo -u www-data php occ maintenance:mode --on

sudo -u www-data php occ config:app:set circles --value 1 group_backend # Mirroring circles as groups
sudo -u www-data php occ config:app:set circles --value 0 allow_listed_circles # Hide circles in shared list, useful with the 'group_backend' option

# sudo -u www-data php occ config:app:set circles --value " " group_backend_name_prefix # Remove default group name prefix
# sudo -u www-data php occ config:app:set circles --value " 🌀" group_backend_name_suffix # Add group name suffix

sudo -u www-data php occ config:app:set circles --value 12 allow_circles # Only show public and closed circles
sudo -u www-data php occ config:app:set circles --value 1 skip_invitation_to_closed_circles

sudo -u www-data php occ config:app:set circles --value 0 allow_files_filtered_by_circles # Disable files list filtering by circles in the Files app
sudo -u www-data php occ config:app:set circles --value 0 allow_adding_any_group_members # Adding group members only for groups where the current user is a member or a global administrator

sudo -u www-data php occ maintenance:mode --off

apk del .maintenance-deps


```

# Credits

App Icon by [Madebyoliver](http://www.flaticon.com/authors/madebyoliver) under Creative Commons BY 3.0
