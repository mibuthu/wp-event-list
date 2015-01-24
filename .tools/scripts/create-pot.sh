#!/bin/bash

path_to_plugin_dir="/../.."
language_dir="/languages"

plugin_path=$(cd "$(dirname "$0")${path_to_plugin_dir}" && pwd)
plugin_slug=$(basename $(ls "${plugin_path}"/*.php) .php)
language_path="${plugin_path}${language_dir}"
#language_file="${language_path}/${plugin_slug}.pot"
language_file="${language_path}/event-list.pot"

# get project information from the plugin header
plugin_name=`awk -F: '/Plugin Name:/ { print $2 }' "${plugin_path}/${plugin_slug}.php" | sed 's/^ *//g'`
plugin_author=`awk -F: '/Author:/ { print $2 }' "${plugin_path}/${plugin_slug}.php" | sed 's/^ *//g'`

# create a template file for translations
mkdir -p "${language_path}"
rm -f "${language_file}"
wp_keywords="-k__ -k_e -k_n:1,2 -k_x:1,2c -k_ex:1,2c -k_nx:4c,1,2 -kesc_attr__ -kesc_attr_e -kesc_attr_x:1,2c -kesc_html__ -kesc_html_e -kesc_html_x:1,2c -k_n_noop:1,2 -k_nx_noop:4c,1,2"
find "${plugin_path}" -iname "*.php" | xargs xgettext --from-code=UTF-8 --default-domain=${plugin_slug} --output="${language_file}" --language=PHP --no-wrap --copyright-holder="${plugin_author}" ${wp_keywords}

# fix header information
now=$(date +%Y)
sed -i "s/SOME DESCRIPTIVE TITLE./This is the translation template file for ${plugin_name}./g" "${language_file}"
sed -i "s/(C) YEAR/(C) ${now}/g" "${language_file}"
sed -i "s/the PACKAGE package./the plugin./g" "${language_file}"

# current plural forms for english
sed -i 's/^"Plural-Forms:.*/"Plural-Forms: nplurals=2; plural=(n != 1);\\n"/' "${language_file}"
