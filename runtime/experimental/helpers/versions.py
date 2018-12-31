#!/usr/bin/env python3
import sys
import json
import argparse

def update(section, item, value):
    data = read()
    data[section][item]=value
    write(data)

def read():
    with open('/versions.json') as f:
        data = json.load(f)
    return data

def write(data):
    with open('/versions.json', 'w') as f:
        json.dump(data, f)

if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument("cmd", help="The command [init, add] you want to run.")
    parser.add_argument("-s", "--section", action="store", const="section", nargs="?", help="The section [libraries, extensions, executables] you want to add.")
    parser.add_argument("-i", "--item", action="store", const="item", nargs="?", help="The item you want to add.")
    parser.add_argument("-v", "--value", action="store", const="value", nargs="?", help="The value you want to add.")
    args = parser.parse_args()

    if args.cmd not in ['init', 'add']:
        parser.print_help
        sys.exit()

    if args.cmd == 'init':
        data = {
            "libraries": {},
            "extensions": {},
            "executables": {}
            }
        write(data)
        sys.exit()

    if args.section not in ["libraries", "extensions", "executables"]:
        print('You must provide a valid section to add.')
        parser.print_help
        sys.exit()

    if not args.item:
        print('You must provide a valid item to add.')
        parser.print_help
        sys.exit()
    
    if not args.value:
        print('You must provide a valid value to add.')
        parser.print_help
        sys.exit()

    update(section=args.section, item=args.item, value=args.value)