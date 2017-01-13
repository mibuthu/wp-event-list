#!/bin/bash

# ===== SETTINGS ===== #

plugin_root_dir="../.."
lang_dir="languages"

plugin_path="$(cd "$(dirname "$0")/${plugin_root_dir}" && pwd)"
plugin_slug="$(basename $(ls "${plugin_path}"/*.php) .php)"
lang_path="${plugin_path}/${lang_dir}"
lang_source="${lang_path}/${plugin_slug}.pot"
debug=0
tx_arg=""

# get project information from the plugin header
plugin_name="$(awk -F: '/Plugin Name:/ { print $2 }' "${plugin_path}/${plugin_slug}.php" | sed 's/^ *//g')"
plugin_author="$(awk -F: '/Author:/ { print $2 }' "${plugin_path}/${plugin_slug}.php" | sed 's/^ *//g')"

# available options
declare -A options
options=(
	[-h, --help]="l10n_help|show this help message and exit"
	[-d, --debug]="l10n_enable_debug|enable debug messages"
)

# available commands
declare -A commands
commands=(
	[update_source]="l10n_update_source|creates or updates the language source file (*.pot)"
	[push_source]="l10n_push_source|push local changes of source pot file to the transifex server"
	[pull_translations]="l10n_pull_translations|pull all translation files from the transifex server, set -a cmd_option to download all translations"
	[pull_translation]="l10n_pull_translation|pull a single translation file, the language (e.g. de_DE) must be given as a cmd_option"
	[compile_translations]="l10n_compile_translations|compile all po translation files to mo files"
	[status]="l10n_status|shows the status of the transifex repository"
	[help]="l10n_help|show this help message and exit"
)

# ===== FUNCTIONS ===== #

# Function to print help messages
# parameters: $1 ... exit code (optional)   The script will exit with the given exit code (default=0).
function l10n_help() {
	echo "Usage: $(basename "$0") [option] command [cmd_option]"
	echo ""
	echo "This script handles all required task for multi localisation support in Wordpress"
	echo "plugins and the exchange the language files with Transifex service."
	echo ""
	echo "Options:"
	for option in "${!options[@]}"; do
		printf "  %-22s%s\n" "$option" "${options[$option]#*|}"
	done
	echo ""
	echo "Commands:"
	for command in "${!commands[@]}"; do
		printf "  %-22s%s\n" "$command" "${commands[$command]#*|}"
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
function l10n_enable_debug() {
	debug=1
	tx_arg="-d"
	# print some general debug messages
	echo "Plugin Slug: $plugin_slug"
	echo "Language Path :$lang_path"
	echo "Language Source: $lang_source"
}

# Function to create and update the language source file (*.pot)
# parameters: none
function l10n_update_source() {
	# create the template file for translations
	mkdir -p "${lang_path}"
	rm -f "${lang_source}"
	wp_keywords="-k__ -k_e -k_n:1,2 -k_x:1,2c -k_ex:1,2c -k_nx:4c,1,2 -kesc_attr__ -kesc_attr_e -kesc_attr_x:1,2c -kesc_html__ -kesc_html_e -kesc_html_x:1,2c -k_n_noop:1,2 -k_nx_noop:4c,1,2"
	cd "${plugin_path}";
	find "." -iname "*.php" | sort | xargs xgettext --from-code=UTF-8 --default-domain=${plugin_slug} --output="${lang_source}" --language=PHP --no-wrap --copyright-holder="${plugin_author}" ${wp_keywords}

	# fix the header information in the file
	now=$(date +%Y)
	sed -i "s/SOME DESCRIPTIVE TITLE./This is the translation template file for ${plugin_name}./g" "${lang_source}"
	sed -i "s/(C) YEAR/(C) ${now}/g" "${lang_source}"
	sed -i "s/the PACKAGE package./the plugin./g" "${lang_source}"

	# current plural forms for english
	sed -i 's/^"Plural-Forms:.*/"Plural-Forms: nplurals=2; plural=(n != 1);\\n"/' "${lang_source}"
}

# Function to push the source pot file to the Transifex server
# parameters: none
function l10n_push_source() {
	tx ${tx_arg} push -s
}

# Function to pull the translation files from the Transifex server
# parameters: $1 ... cmd_option (optional)    With the value "-a" all translation files will be downloads (standard: only locally available translations)
function l10n_pull_translations() {
	local arg=""
	[ "$1" = "-a" ] && arg="-a" 
	tx ${tx_arg} pull ${arg}
}

# Function to pull a translation file from the Transifex server (normally used to add a new translation which isn't availabe locally)
# parameters: $1 ... cmd_option (required)    The language to download must be provided (e.g. "de_DE")
function l10n_pull_translation() {
	if [ -z "$1" ]; then
		# show error, print help, then exit (if no cmd_option was provided)
		echo -e "ERROR: Required cmd_option (language to download) is missing!\n"
		l10n_help 1
	fi
	if [[ "$1" =~ [a-z]{2}_[A-Z]{2} ]]; then
		tx ${tx_arg} pull -l $1
	else
		# show error, print help, then exit (if cmd_option in wrong format was provided)
		echo -e "ERROR: Required cmd_option (language to download) is given in a wrong format!\n"
		l10n_help 1
	fi
}

# Function to compile all po translation files to mo files
# parameter: none
function l10n_compile_translations() {
	for po_file in $(ls "${lang_path}/${plugin_slug}"*.po); do
		po_file=$(basename $po_file .po)
		echo "compiling    ${po_file}.po  ->  ${po_file}.mo"
		msgcat "${lang_path}/${po_file}.po" | msgfmt -o "${lang_path}/${po_file}.mo" -
	done
}

# Function to show the status of the Transifex repository (tx status)
# parameters: none
function l10n_status() {
	tx ${tx_arg} status
}

# ===== MAIN PROGRAM ===== #

arg="$1"
cmd_option="$2"

# check for option args (only 1 option can be handled)
if [ "${arg:0:1}" = "-" ]; then
	valid_option=0
	for optionname in "${!options[@]}"; do
		if [ "${optionname%, *}" = "$arg" -o "${optionname#*, }" = "$arg" ]; then
			valid_option=1
			arg=$2
			cmd_option=$3
			${options[$optionname]%%|*}
			break
		fi
	done
	if [ $valid_option -eq 0 ]; then
		# show error, print help, then exit (if an invalid option was provided)
		echo -e "ERROR: Invalid option provided!\n"
		l10n_help 1
	fi
fi

# check of command arg
if [ -z "$arg" ]; then
	# show error, print help, then exit (if no command was provided)
	echo -e "ERROR: Command is missing!\n"
	l10n_help 1
fi
if [ -n "${commands[$arg]}" ]; then
	${commands[$arg]%%|*} ${cmd_option}
else
	# show error, print help, then exit (if an invalid command was provided)
	echo -e "ERROR: Invalid command provided!\n"
	l10n_help 1
fi
exit 0

