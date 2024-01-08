import { Fragment, useRef, useState } from 'react'
import { Dialog, Switch, Transition } from '@headlessui/react';
import { ArrowPathIcon } from '@heroicons/react/24/outline'

function classNames(...classes) {
    return classes.filter(Boolean).join(' ')
}

export default function SentryCheckout({ open, setOpen, price, linkSingle, linkSubscription }) {
    const [renewalEnabled, setRenewalEnabled] = useState(false);
    const cancelButtonRef = useRef(null);

    return (
        <Transition.Root show={open} as={Fragment}>
            <Dialog as="div" className="relative z-10" initialFocus={cancelButtonRef} onClose={setOpen}>
                <Transition.Child
                    as={Fragment}
                    enter="ease-out duration-300"
                    enterFrom="opacity-0"
                    enterTo="opacity-100"
                    leave="ease-in duration-200"
                    leaveFrom="opacity-100"
                    leaveTo="opacity-0"
                >
                    <div className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
                </Transition.Child>

                <div className="fixed inset-0 z-10 w-screen overflow-y-auto">
                    <div className="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                        <Transition.Child
                            as={Fragment}
                            enter="ease-out duration-300"
                            enterFrom="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                            enterTo="opacity-100 translate-y-0 sm:scale-100"
                            leave="ease-in duration-200"
                            leaveFrom="opacity-100 translate-y-0 sm:scale-100"
                            leaveTo="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        >
                            <Dialog.Panel className="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-xl">
                                <div className="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                                    <div className="sm:flex sm:items-start">
                                        <div className="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                                            <ArrowPathIcon className="h-6 w-6 text-blue-600" aria-hidden="true" />
                                        </div>
                                        <div className="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                                            <Dialog.Title as="h3" className="text-base font-semibold leading-6 text-gray-900">
                                                Enable yearly license renewal?
                                            </Dialog.Title>
                                            <div className="mt-2">
                                                <div className="text-sm text-gray-500">
                                                    Do you want to enable automatic license renewal every year?
                                                </div>
                                                <div className="mt-2 text-sm text-gray-500">
                                                    Without license renewal, you will stop receiving updates after 1
                                                    year.
                                                </div>
                                                <div className="mt-2 text-sm text-gray-500">
                                                    With license renewal, the license renews automatically every year so <strong>you always get the latest updates</strong>. You also get a 10% discount on the price. Renewal can be cancelled at any time.
                                                </div>
                                            </div>
                                            <Switch.Group as="div" className="mt-5 flex items-center">
                                                <Switch
                                                    checked={renewalEnabled}
                                                    onChange={setRenewalEnabled}
                                                    className={classNames(
                                                        renewalEnabled ? '!bg-blue-500' : '!bg-gray-200',
                                                        'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2'
                                                    )}
                                                >
                                                    <span
                                                        aria-hidden="true"
                                                        className={classNames(
                                                            renewalEnabled ? 'translate-x-5' : 'translate-x-0',
                                                            'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                                                        )}
                                                    />
                                                </Switch>
                                                <Switch.Label as="span" className="ml-3 text-sm cursor-pointer">
                                                    <span className="font-medium text-gray-900">Automatic license renewal</span>{' '}
                                                    <span className="text-gray-500">(every year)</span>
                                                </Switch.Label>
                                            </Switch.Group>
                                            {renewalEnabled ? (
                                                <div className="mt-5 text-sm text-gray-500">
                                                    {price[1]}€ per year, cancel anytime,{" "}
                                                    <span className="text-gray-900 font-medium">
                                                        {" "}all future updates
                                                    </span>
                                                </div>
                                            ) : (
                                                <div className="mt-5 text-sm text-gray-500">
                                                    {price[0]}€ one-time payment,{" "}
                                                    <span className="text-gray-900 font-medium">
                                                        {" "}no updates after 1 year
                                                    </span>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                                <div className="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                                    <button
                                        type="button"
                                        className="inline-flex w-full justify-center rounded-md !bg-blue-500 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:!bg-blue-600 sm:ml-3 sm:w-auto"
                                        onClick={() => {
                                            setOpen(false);
                                            window.open(renewalEnabled ? linkSubscription : linkSingle, "_blank");
                                        }}
                                    >
                                        Continue to checkout
                                    </button>
                                    <button
                                        type="button"
                                        className="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto"
                                        onClick={() => setOpen(false)}
                                        ref={cancelButtonRef}
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </Dialog.Panel>
                        </Transition.Child>
                    </div>
                </div>
            </Dialog>
        </Transition.Root>
    )
}
