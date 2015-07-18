#!/bin/bash

# ===== SETTINGS ===== #

plugin_root_dir="../.."
lang_dir="languages"

plugin_path="$(cd "$(dirname "$0")/${plugin_root_dir}" && pwd)"
plugin_slug="$(basename $(ls "${plugin_path}"/*.php) .php)"
lang_path="${plugin_path}/${lang_dir}"
lang_source="${lang_path}/${plugin_slug}.pot"
debug=0

# get project information from the plugin header
plugin_name="$(awk -F: '/Plugin Name:/ { print $2 }' "${plugin_path}/${plugin_slug}.php" | sed 's/^ *//g')"
plugin_author="$(awk -F: '/Author:/ { print $2 }' "${plugin_path}/${plugin_slug}.php" | sed 's/^ *//g')"

# available options
declare -A options
options=(
	[-h, --help]="lang_help|show this help message and exit"
	[-d, --debug]="lang_enable_debug|enable debug messages"
)

# available commands
declare -A commands
commands=(
	[help]="lang_help|show this help message and exit"
#	[tx_init]="|not implemented yet"
#	[tx_pull]="|not implemented yet"
#	[tx_push]="|not implemented yet"
#	[tx_set]="|not implemented yet"
#	[tx_status]="|not implemented yet"
	[update_source]="lang_update_source|creates or updates the language source file (*.pot)"
#	[push_source]="|not implemented yet"
#	[add_language]="|not implemented yet"
#	[pull_languages]="|not implemented yet"
#	[create_mo]="|not implemented yet"
)

# ===== FUNCTIONS ===== #

# Function to print help messages
# parameters: $1 ... exit code (optional)   The script will exit with the given exit code (default=0).
function lang_help () {
	echo "Usage: $(basename "$0") [option] command [cmd_options]"
	echo ""
	echo "This script handles all required task for multi language support in Wordpress"
	echo "plugins and the exchange the language files with Transifex service."
	echo ""
	echo "Options:"
	for option in "${!options[@]}"; do
		printf "  %-18s%s\n" "$option" "${options[$option]#*|}"
	done
	echo ""
	echo "Commands:"
	for command in "${!commands[@]}"; do
		printf "  %-18s%s\n" "$command" "${commands[$command]#*|}"
	done
	if [[ $1 =~ ^[0-9]+$ ]] ; then
		echo -e "\nScript aborted! You can try to enable debug messages with -d if you don't know why."
		exit $1
	else
		exit 0
	fi
}

# Function to enable debug messages and already print some general debug info
# parameters: none
function lang_enable_debug () {
	debug=1
	# print some general debug messages
	echo "Plugin Slug: $plugin_slug"
	echo "Language Path :$lang_path"
	echo "Language Source: $lang_source"
}

# Function to create and update the language source file (*.pot)
# parameters: none
function lang_update_source () {
	# create the template file for translations
	mkdir -p "${lang_path}"
	rm -f "${lang_source}"
	wp_keywords="-k__ -k_e -k_n:1,2 -k_x:1,2c -k_ex:1,2c -k_nx:4c,1,2 -kesc_attr__ -kesc_attr_e -kesc_attr_x:1,2c -kesc_html__ -kesc_html_e -kesc_html_x:1,2c -k_n_noop:1,2 -k_nx_noop:4c,1,2"
	find "${plugin_path}" -iname "*.php" | sort | xargs xgettext --from-code=UTF-8 --default-domain=${plugin_slug} --output="${lang_source}" --language=PHP --no-wrap --copyright-holder="${plugin_author}" ${wp_keywords}

	# fix the header information in the file
	now=$(date +%Y)
	sed -i "s/SOME DESCRIPTIVE TITLE./This is the translation template file for ${plugin_name}./g" "${lang_source}"
	sed -i "s/(C) YEAR/(C) ${now}/g" "${lang_source}"
	sed -i "s/the PACKAGE package./the plugin./g" "${lang_source}"

	# current plural forms for english
	sed -i 's/^"Plural-Forms:.*/"Plural-Forms: nplurals=2; plural=(n != 1);\\n"/' "${lang_source}"
}


# ===== MAIN PROGRAM ===== #

arg="$1"

# check for option args (only 1 option can be handled)
if [ "${arg:0:1}" = "-" ]; then
	valid_option=0
	for optionname in "${!options[@]}"; do
		if [ "${optionname%, *}" = "$arg" -o "${optionname#*, }" = "$arg" ]; then
			valid_option=1
			arg=$2
			${options[$optionname]%%|*}
			break
		fi
	done
	if [ $valid_option -eq 0 ]; then
		# show error, print help, then exit (if an invalid option was provided)
		echo -e "ERROR: Invalid option provided!\n"
		lang_help 1
	fi
fi

# check of command arg
if [ -z "$arg" ]; then
	# show error, print help, then exit (if no command was provided)
	echo -e "ERROR: Command is missing!\n"
	lang_help 1
fi
if [ -n "${commands[$arg]}" ]; then
	${commands[$arg]%%|*}
else
	# show error, print help, then exit (if an invalid command was provided)
	echo -e "ERROR: Invalid command provided!\n"
	lang_help 1
fi
exit 0

