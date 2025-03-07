import brefCloudIcon from '../../components/icon.svg';
import awsIcon from '../../components/icons/AWS.svg';
import Image from 'next/image';
import { useState, useEffect } from 'react';

export default function HowItWorks() {
    const [step, setStep] = useState(1);

    useEffect(() => {
        const interval = setInterval(() => {
            setStep((currentStep) => currentStep === 5 ? 1 : currentStep + 1);
        }, 2000);

        return () => clearInterval(interval);
    }, []);

    return (
        <div className="bg-white py-24 sm:py-32">
            <div className="mx-auto max-w-2xl px-6 lg:max-w-4xl lg:px-8">
                <div className="mx-auto max-w-4xl text-center">
                    <p className="text-base/7 font-semibold text-blue-500">Cloud deployments</p>
                    <h2 className="mt-2 text-pretty text-4xl sm:text-5xl font-black tracking-tight text-gray-950">
                        How it works
                    </h2>
                </div>
                <div className="mt-16 relative w-full aspect-video text-gray-700 font-semibold text-sm">
                    {/* Icons */}
                    <div
                        className="absolute top-0 left-0 h-36 w-40 flex flex-col items-center justify-center gap-3 bg-white shadow ring-1 ring-gray-100 rounded-2xl">
                        <Image src={brefCloudIcon} alt="Bref Cloud" className="size-16" />
                        <div>bref.cloud</div>
                    </div>
                    <div
                        className="absolute top-0 right-0 h-36 w-40 flex flex-col items-center justify-center gap-3 bg-white shadow ring-1 ring-gray-100 rounded-2xl">
                        <Image src={awsIcon} alt="AWS" className="size-16" />
                        <div>Your AWS account</div>
                    </div>
                    <div
                        className="absolute bottom-0 left-0 h-36 w-40 flex flex-col items-center justify-center gap-3 bg-white shadow ring-1 ring-gray-100 rounded-2xl">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill={"none"} className="size-16 text-current">
                            <path d="M20 14.5V6.5C20 4.61438 20 3.67157 19.4142 3.08579C18.8284 2.5 17.8856 2.5 16 2.5H8C6.11438 2.5 5.17157 2.5 4.58579 3.08579C4 3.67157 4 4.61438 4 6.5V14.5" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
                            <path d="M3.49762 15.5154L4.01953 14.5H19.9518L20.5023 15.5154C21.9452 18.177 22.3046 19.5077 21.7561 20.5039C21.2077 21.5 19.7536 21.5 16.8454 21.5L7.15462 21.5C4.24642 21.5 2.79231 21.5 2.24387 20.5039C1.69543 19.5077 2.05474 18.177 3.49762 15.5154Z" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
                            <path opacity="0.4" d="M15.5 7L16.4199 7.79289C16.8066 8.12623 17 8.29289 17 8.5C17 8.70711 16.8066 8.87377 16.4199 9.20711L15.5 10" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
                            <path opacity="0.4" d="M8.5 7L7.58009 7.79289C7.19337 8.12623 7 8.29289 7 8.5C7 8.70711 7.19336 8.87377 7.58009 9.20711L8.5 10" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
                            <path opacity="0.4" d="M13 6L11 11" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
                        </svg>
                        <div className="font-mono text-sm">$ bref deploy</div>
                    </div>
                    <div
                        className="absolute bottom-0 right-0 h-36 w-40 flex flex-col items-center justify-center gap-3 bg-white shadow ring-1 ring-gray-100 rounded-2xl">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill={"none"} className="size-16 text-current">
                            <path opacity="0.4" d="M8.21365 16.3972C9.29579 15.6726 10.5995 15.25 12 15.25C13.4005 15.25 14.7042 15.6726 15.7864 16.3972C16.8749 17.126 17.25 18.3957 17.25 19.5C17.25 19.9142 16.9142 20.25 16.5 20.25H7.5C7.08579 20.25 6.75 19.9142 6.75 19.5C6.75 18.3957 7.12515 17.126 8.21365 16.3972Z" fill="currentColor" />
                            <path opacity="0.4" d="M8.75 11C8.75 9.20507 10.2051 7.75 12 7.75C13.7949 7.75 15.25 9.20507 15.25 11C15.25 12.7949 13.7949 14.25 12 14.25C10.2051 14.25 8.75 12.7949 8.75 11Z" fill="currentColor" />
                            <path d="M14.75 6.5C14.75 4.98122 15.9812 3.75 17.5 3.75C19.0188 3.75 20.25 4.98122 20.25 6.5C20.25 8.01878 19.0188 9.25 17.5 9.25C15.9812 9.25 14.75 8.01878 14.75 6.5Z" fill="currentColor" />
                            <path d="M3.75 6.5C3.75 4.98122 4.98122 3.75 6.5 3.75C8.01878 3.75 9.25 4.98122 9.25 6.5C9.25 8.01878 8.01878 9.25 6.5 9.25C4.98122 9.25 3.75 8.01878 3.75 6.5Z" fill="currentColor" />
                            <path d="M7.54682 10.3485C7.51597 10.5612 7.5 10.7787 7.5 11C7.5 12.4176 8.15548 13.6821 9.17989 14.5069C8.6532 14.7034 8.15246 14.9533 7.68468 15.25H2.5C2.08579 15.25 1.75 14.9142 1.75 14.5C1.75 13.4263 2.07 12.1626 3.05401 11.4213C4.02988 10.6863 5.21666 10.25 6.5 10.25C6.85725 10.25 7.20701 10.2838 7.54682 10.3485Z" fill="currentColor" />
                            <path d="M16.3153 15.25H21.5C21.9142 15.25 22.25 14.9142 22.25 14.5C22.25 13.4263 21.93 12.1626 20.946 11.4213C19.9701 10.6863 18.7834 10.25 17.5 10.25C17.1428 10.25 16.793 10.2838 16.4532 10.3485C16.4841 10.5612 16.5 10.7787 16.5 11C16.5 12.4176 15.8445 13.6821 14.8201 14.5069C15.3468 14.7034 15.8476 14.9533 16.3153 15.25Z" fill="currentColor" />
                        </svg>
                        <div>Website visitors</div>
                    </div>

                    {/* Step 1 */}
                    <div className={`absolute inset-0 py-36 h-full w-40 flex justify-center transition-opacity duration-500 ${step === 1 ? 'opacity-100' : 'opacity-0'}`}>
                        <div className="h-full w-full flex justify-center relative">
                            <svg viewBox="0 0 100 200" xmlns="http://www.w3.org/2000/svg" className="max-h-full text-blue-500 opacity-75">
                                <path d="M50 180 L50 35 M50 20 L35 40 M50 20 L65 40" stroke="currentColor" strokeWidth="5" fill="none" strokeLinecap="round" strokeLinejoin="round" />
                            </svg>
                            <div className="absolute inset-0 flex flex-col gap-1 justify-center items-center">
                                <div className="flex items-center justify-center size-6 rounded-full bg-gray-100 ring-1 ring-gray-200 text-gray-700">
                                    <div>1</div>
                                </div>
                                <div className="px-1 text-center font-semibold text-xs bg-white text-gray-600 rounded">Get AWS credentials</div>
                            </div>
                        </div>
                    </div>

                    {/* Step 2 */}
                    <div className={`absolute inset-0 px-40 py-36 h-full w-full flex justify-center transition-opacity duration-500 ${step === 2 ? 'opacity-100' : 'opacity-0'}`}>
                        <div className="h-full w-full flex justify-center relative">
                            <svg viewBox="0 0 760 160" xmlns="http://www.w3.org/2000/svg" className="max-h-full text-blue-500 opacity-75">
                                <path d="M76 190 L664 -10 M684 -20 L634 -30 M684 -20 L660 25" stroke="currentColor" strokeWidth="7" fill="none" strokeLinecap="round" strokeLinejoin="round" />
                            </svg>
                            <div className="absolute inset-0 flex gap-1 justify-center items-center">
                                <div className="flex items-center justify-center size-6 rounded-full bg-gray-100 ring-1 ring-gray-200 text-gray-700">
                                    <div>2</div>
                                </div>
                                <div className="px-1 text-center font-semibold text-xs bg-white text-gray-600 rounded">Deploy</div>
                            </div>
                        </div>
                    </div>

                    {/* Step 3 */}
                    <div className={`absolute top-0 right-0 bottom-0 py-36 h-full w-40 flex justify-center transition-opacity duration-500 ${step === 3 ? 'opacity-100' : 'opacity-0'}`}>
                        <div className="h-full w-full flex justify-center relative">
                            <svg viewBox="0 0 100 200" xmlns="http://www.w3.org/2000/svg" className="max-h-full text-blue-500 opacity-75">
                                <path d="M50 180 L50 35 M50 20 L35 40 M50 20 L65 40" stroke="currentColor" strokeWidth="5" fill="none" strokeLinecap="round" strokeLinejoin="round" />
                            </svg>
                            <div className="absolute inset-0 flex flex-col gap-1 justify-center items-center">
                                <div className="flex items-center justify-center size-6 rounded-full bg-gray-100 ring-1 ring-gray-200 text-gray-700">
                                    <div>3</div>
                                </div>
                                <div className="px-1 text-center font-semibold text-xs bg-white text-gray-600 rounded">Your app is up and serving traffic</div>
                            </div>
                        </div>
                    </div>

                    {/* Step 4 */}
                    <div className={`absolute inset-0 py-36 h-full w-40 flex justify-center transition-opacity duration-500 ${step === 4 ? 'opacity-100' : 'opacity-0'}`}>
                        <div className="h-full w-full flex justify-center relative">
                            <svg viewBox="0 0 100 200" xmlns="http://www.w3.org/2000/svg" className="max-h-full text-blue-500 opacity-75">
                                <path d="M50 180 L50 35 M50 20 L35 40 M50 20 L65 40" stroke="currentColor" strokeWidth="5" fill="none" strokeLinecap="round" strokeLinejoin="round" />
                            </svg>
                            <div className="absolute inset-0 flex flex-col gap-1 justify-center items-center">
                                <div className="flex items-center justify-center size-6 rounded-full bg-gray-100 ring-1 ring-gray-200 text-gray-700">
                                    <div>4</div>
                                </div>
                                <div className="px-1 text-center font-semibold text-xs bg-white text-gray-600 rounded">Monitor via Bref Cloud</div>
                            </div>
                        </div>
                    </div>
                    <div className={`absolute top-0 right-0 bottom-0 h-36 w-full px-48 flex justify-center transition-opacity duration-500 ${step === 4 ? 'opacity-100' : 'opacity-0'}`}>
                        <div className="h-full w-full flex justify-center relative">
                            <svg viewBox="0 0 760 144" xmlns="http://www.w3.org/2000/svg" className="max-h-full text-blue-500 opacity-75">
                                <path d="M20 72 L712 72 M740 72 L700 52 M740 72 L700 92" stroke="currentColor" strokeWidth="7" fill="none" strokeLinecap="round" strokeLinejoin="round" />
                            </svg>
                            <div className="absolute inset-0 flex gap-1 justify-center items-center">
                                <div className="flex items-center justify-center size-6 rounded-full bg-gray-100 ring-1 ring-gray-200 text-gray-700">
                                    <div>4</div>
                                </div>
                                <div className="px-1 text-center font-semibold text-xs bg-white text-gray-600 rounded">Secure access via the AWS API</div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    )
}
