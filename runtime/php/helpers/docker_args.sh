#!/bin/bash

# $1 the file name to parse
# $2 the section we are interested in.

output=""
while IFS='= ' read var val
do
    # If a new section
    if [[ $var == \[*] ]]
    then
        section=$var
    elif [[ $val ]] && [ "$section" == "[$2]" ] &&  [[ ! -z "${val// }" ]]
    then
        # If not a comment
        if [[ ! $var =~ ^\;.*  ]]; then
          output="$output --build-arg $var=$val "
        fi
    fi
done < $1

echo $output