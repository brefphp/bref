import brefCloudIcon from '../../components/icon.svg';
import awsIcon from '../../components/icons/AWS.svg';
import Image from 'next/image';
import { useState } from 'react';

export default function HowItWorks() {
    const [step, setStep] = useState(1);

    const nextStep = () => setStep((current) => current === 5 ? 1 : current + 1);
    const prevStep = () => setStep((current) => current === 1 ? 5 : current - 1);

    return (
        <div className="bg-white py-24 sm:py-32">
            <div className="mx-auto max-w-2xl px-6 lg:max-w-4xl lg:px-8">

                <div className="mx-auto text-center">
                    <p className="text-base/7 font-semibold text-blue-500">Cloud deployments</p>
                    <h2 className="mt-2 text-pretty text-4xl sm:text-5xl font-black tracking-tight text-gray-950">
                        How it works
                    </h2>
                </div>

                <div className="mt-16 mx-10">

                    <div className="relative w-full aspect-video text-gray-700 font-semibold text-sm">
                        {/* Navigation Buttons */}
                        <button onClick={prevStep} className="absolute left-0 top-1/2 -translate-y-1/2 -ml-16 p-2 rounded-full bg-white shadow hover:bg-gray-50 ring-1 ring-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:bg-white" disabled={step === 1}>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="size-6">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                            </svg>
                        </button>
                        <button onClick={nextStep} className={`absolute right-0 top-1/2 -translate-y-1/2 -mr-16 p-2 rounded-full bg-white shadow hover:bg-gray-50 ring-1 ring-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 ${step === 1 ? 'animate-bounce ring-2 ring-blue-500' : ''}`}>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="size-6">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </button>

                        {/* Icons */}
                        <div className={`absolute top-0 left-0 h-36 w-40 flex flex-col items-center justify-center gap-3 bg-white shadow ring-1 ring-gray-100 rounded-2xl transition-all duration-200 ${step !== 3 ? 'opacity-100' : 'opacity-50'}`}>
                            <Image src={brefCloudIcon} alt="Bref Cloud" className="size-16" />
                            <div>bref.cloud</div>
                        </div>
                        <div className={`absolute top-0 right-0 h-36 w-40 flex flex-col items-center justify-center gap-3 bg-white shadow ring-1 ring-gray-100 rounded-2xl transition-opacity duration-200 ${step >= 2 ? 'opacity-100' : 'opacity-50'}`}>
                            <Image src={awsIcon} alt="AWS" className="size-16" />
                            <div>Your AWS account</div>
                        </div>
                        <div className={`absolute bottom-0 left-0 h-36 w-40 flex flex-col items-center justify-center gap-3 bg-white shadow ring-1 ring-gray-100 rounded-2xl transition-opacity duration-200 ${step !==3 ? 'opacity-100' : 'opacity-50'}`}>
                            <LaptopIcon className="size-16 text-current" />
                            {step <= 3 && <div className="font-mono text-sm">$ bref deploy</div>}
                        </div>
                        <div className={`absolute bottom-0 left-0 h-36 w-40 flex flex-col items-center justify-center gap-3 bg-white shadow ring-1 ring-gray-100 rounded-2xl ml-24 transition-opacity duration-200 ${step === 5 ? 'opacity-100' : 'opacity-0'}`}>
                            <LaptopIcon className="size-16 text-current" />
                        </div>
                        <div className={`absolute bottom-0 left-0 h-36 w-40 flex flex-col items-center justify-center gap-3 bg-white shadow ring-1 ring-gray-100 rounded-2xl ml-28 transition-opacity duration-200 ${step === 5 ? 'opacity-100' : 'opacity-0'}`}>
                            <LaptopIcon className="size-16 text-current" />
                        </div>
                        <div className={`absolute bottom-0 left-0 h-36 w-40 flex flex-col items-center justify-center gap-3 bg-white shadow ring-1 ring-gray-100 rounded-2xl ml-32 transition-opacity duration-200 ${step === 5 ? 'opacity-100' : 'opacity-0'}`}>
                            <LaptopIcon className="size-16 text-current" />
                        </div>
                        <div className={`absolute bottom-0 right-0 h-36 w-40 flex flex-col items-center justify-center gap-3 bg-white shadow ring-1 ring-gray-100 rounded-2xl transition-opacity duration-200 ${step >= 3 ? 'opacity-100' : 'opacity-50'}`}>
                            <PhoneIcon className="size-12 text-current" />
                            <div>End users</div>
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
                                    <div className="px-1 text-center font-semibold text-xs bg-white text-gray-600 rounded text-pretty">Start
                                        deployment & retrieve AWS credentials
                                    </div>
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
                                    <div className="px-1 text-center font-semibold text-xs bg-white text-gray-600 rounded text-pretty">Deploy
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Step 3 */}
                        <div className={`absolute top-0 right-0 bottom-0 py-36 h-full w-40 flex justify-center transition-opacity duration-500 ${step >= 3 ? 'opacity-100' : 'opacity-0'}`}>
                            <div className="h-full w-full flex justify-center relative">
                                <svg viewBox="0 0 100 200" xmlns="http://www.w3.org/2000/svg" className="max-h-full text-blue-500 opacity-75">
                                    <path d="M50 180 L50 35 M50 20 L35 40 M50 20 L65 40" stroke="currentColor" strokeWidth="5" fill="none" strokeLinecap="round" strokeLinejoin="round" />
                                </svg>
                                <div className="absolute inset-0 flex flex-col gap-1 justify-center items-center">
                                    <div className="flex items-center justify-center size-6 rounded-full bg-gray-100 ring-1 ring-gray-200 text-gray-700">
                                        <div>3</div>
                                    </div>
                                    <div className="px-1 text-center font-semibold text-xs bg-white text-gray-600 rounded text-pretty">App
                                        is up and serving traffic
                                    </div>
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
                                    <div className="px-1 text-center font-semibold text-xs bg-white text-gray-600 rounded text-pretty">
                                        Monitor via Bref Cloud
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className={`absolute top-0 right-0 bottom-0 h-36 w-full px-48 flex justify-center transition-opacity duration-500 ${step >= 4 ? 'opacity-100' : 'opacity-0'}`}>
                            <div className="h-full w-full flex justify-center relative">
                                <svg viewBox="0 0 760 144" xmlns="http://www.w3.org/2000/svg" className="max-h-full text-blue-500 opacity-75">
                                    <path d="M20 72 L712 72 M740 72 L700 52 M740 72 L700 92" stroke="currentColor" strokeWidth="7" fill="none" strokeLinecap="round" strokeLinejoin="round" />
                                </svg>
                            </div>
                        </div>

                        {/* Step 5 */}
                        <div className={`absolute inset-0 py-36 h-full w-40 flex justify-center transition-opacity duration-500 ${step === 5 ? 'opacity-100' : 'opacity-0'}`}>
                            <div className="h-full w-full flex justify-center relative">
                                <svg viewBox="0 0 100 200" xmlns="http://www.w3.org/2000/svg" className="max-h-full text-blue-500 opacity-75">
                                    <path d="M50 180 L50 35 M50 20 L35 40 M50 20 L65 40" stroke="currentColor" strokeWidth="5" fill="none" strokeLinecap="round" strokeLinejoin="round" />
                                </svg>
                                <div className="absolute inset-0 flex flex-col gap-1 justify-center items-center">
                                    <div className="flex items-center justify-center size-6 rounded-full bg-gray-100 ring-1 ring-gray-200 text-gray-700">
                                        <div>5</div>
                                    </div>
                                    <div className="px-1 text-center font-semibold text-xs bg-white text-gray-600 rounded text-pretty">
                                        Invite team members
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className={`absolute inset-0 py-36 h-full ml-20 w-40 flex justify-center transition-opacity duration-500 ${step === 5 ? 'opacity-100' : 'opacity-0'}`}>
                            <div className="h-full w-full flex justify-center relative">
                                <svg viewBox="0 0 100 200" xmlns="http://www.w3.org/2000/svg" className="max-h-full text-blue-500 opacity-75">
                                    <path d="M70 180 L20 35 M15 20 L5 40 M15 20 L35 35" stroke="currentColor" strokeWidth="5" fill="none" strokeLinecap="round" strokeLinejoin="round" />
                                </svg>
                            </div>
                        </div>

                    </div>

                    <div className="mt-10 mx-auto max-w-lg text-sm text-gray-600 flex items-start gap-4">
                        <div className="flex items-center justify-center size-6 px-2 rounded-full bg-gray-100 ring-1 ring-gray-200 text-gray-700 font-semibold">
                            <div>{step}</div>
                        </div>
                        {/* Step 1 */}
                        {step === 1 && <div>You start a deployment by running `<code className="font-mono font-semibold text-xs">bref deploy</code>` on your machine or in CI/CD. The CLI authenticates to Bref Cloud, reports the deployment, and retrieves temporary AWS credentials.</div>}
                        {/* Step 2 */}
                        {step === 2 && <div>The CLI uses AWS CloudFormation to set up the infrastructure and deploy your application. The application lives in your AWS account.</div>}
                        {/* Step 3 */}
                        {step === 3 && <div>Your application is now up and running in your AWS account, serving traffic to your users.</div>}
                        {/* Step 4 */}
                        {step === 4 && <div>You can monitor your application via the Bref Cloud dashboard without having to mess with AWS.</div>}
                        {/* Step 5 */}
                        {step === 5 && <div>You can invite team members to Bref Cloud, with different level of permissions, without giving direct access to AWS.</div>}
                    </div>
                </div>
            </div>
        </div>
    )
}

function LaptopIcon(props) {
    return <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill={'none'} {...props}>
        <path
            d="M20 14.5V6.5C20 4.61438 20 3.67157 19.4142 3.08579C18.8284 2.5 17.8856 2.5 16 2.5H8C6.11438 2.5 5.17157 2.5 4.58579 3.08579C4 3.67157 4 4.61438 4 6.5V14.5" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" /><path d="M3.49762 15.5154L4.01953 14.5H19.9518L20.5023 15.5154C21.9452 18.177 22.3046 19.5077 21.7561 20.5039C21.2077 21.5 19.7536 21.5 16.8454 21.5L7.15462 21.5C4.24642 21.5 2.79231 21.5 2.24387 20.5039C1.69543 19.5077 2.05474 18.177 3.49762 15.5154Z" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" /><path opacity="0.4" d="M15.5 7L16.4199 7.79289C16.8066 8.12623 17 8.29289 17 8.5C17 8.70711 16.8066 8.87377 16.4199 9.20711L15.5 10" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" /><path opacity="0.4" d="M8.5 7L7.58009 7.79289C7.19337 8.12623 7 8.29289 7 8.5C7 8.70711 7.19336 8.87377 7.58009 9.20711L8.5 10" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" /><path opacity="0.4" d="M13 6L11 11" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" /></svg>;
}

function PhoneIcon(props) {
    return <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width={36} height={36} color={"#000000"} fill={"none"} {...props}><path opacity="0.4" d="M21 20C20.3567 18.7133 19 17.0243 19 15.5279C19 13.8295 19.3671 11.7341 18.5777 10.1554C18.2445 9.48892 18 8.81397 18 8.05573V4.42857C18 4.19188 17.8081 4 17.5714 4C16.1513 4 15 5.15127 15 6.57143M8 18L11.6348 20.2717C11.8755 20.4222 12.0814 20.6222 12.2389 20.8583L13 22" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" /><path d="M5.02734 15C5.08201 16.0967 5.24516 16.7809 5.73203 17.2678C6.46426 18 7.64277 18 9.99979 18C12.3568 18 13.5353 18 14.2676 17.2678C14.9998 16.5355 14.9998 15.357 14.9998 13V7C14.9998 4.64298 14.9998 3.46447 14.2676 2.73223C13.5353 2 12.3568 2 9.99979 2C7.64277 2 6.46426 2 5.73203 2.73223C5.24516 3.2191 5.08201 3.90328 5.02734 5" stroke="currentColor" strokeWidth="1.5" strokeLinejoin="round" /><path d="M4.25 7.5H5.75C6.44036 7.5 7 6.94036 7 6.25C7 5.55964 6.44036 5 5.75 5L4.25 5C3.55964 5 3 5.55964 3 6.25C3 6.94036 3.55964 7.5 4.25 7.5ZM4.25 7.5L6.75 7.5C7.44036 7.5 8 8.05964 8 8.75C8 9.44036 7.44036 10 6.75 10L4.25 10M4.25 7.5C3.55964 7.5 3 8.05964 3 8.75C3 9.44036 3.55964 10 4.25 10M4.25 10L5.75 10C6.44036 10 7 10.5596 7 11.25C7 11.9404 6.44036 12.5 5.75 12.5H4.25M4.25 10C3.55964 10 3 10.5596 3 11.25C3 11.9404 3.55964 12.5 4.25 12.5M4.25 12.5H5.25C5.94036 12.5 6.5 13.0596 6.5 13.75C6.5 14.4404 5.94036 15 5.25 15H4.25C3.55964 15 3 14.4404 3 13.75C3 13.0596 3.55964 12.5 4.25 12.5Z" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" /><path opacity="0.4" d="M9.99981 15H10.0088" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" /></svg>;
}
