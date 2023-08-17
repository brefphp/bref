import GoldSponsor from './sponsors/gold-sponsor';
import craftLogo from './sponsors/logo-craft-cms.png';
import tidewaysLogo from './sponsors/logo-tideways.svg';
import myBuilderLogo from './sponsors/logo-mybuilder.svg';
import shippyProLogo from './sponsors/logo-shippypro.png';
import nullLogo from './sponsors/logo-null.png';
import awsLogo from './sponsors/logo-aws.svg';
import jetbrainsLogo from './sponsors/logo-jetbrains.svg';
import laravelLogo from './sponsors/logo-laravel.svg';
import depotLogo from './sponsors/logo-depot.png';
import secumailerLogo from './sponsors/logo-secumailer.svg';
import ecomailLogo from './sponsors/logo-ecomail.png';
import PremiumSponsor from './sponsors/premium-sponsor';

export default function Sponsors() {
    return (
        <div className="home-container home-section">
            <h2 className="text-center text-3xl font-black leading-8 text-gray-900">
                They sponsor the open-source project ❤️
            </h2>

            <h3 className="mt-16 text-lg font-bold text-gray-700">
                Premium sponsors
            </h3>
            <div className="mt-4 -mx-6 grid grid-cols-2 gap-0.5 overflow-hidden sm:mx-0 sm:rounded-2xl md:grid-cols-3">
                <PremiumSponsor src={craftLogo} alt="Craft CMS" href="https://craftcms.com/?ref=bref.sh" />
                <PremiumSponsor src={tidewaysLogo} alt="Tideways" href="https://tideways.com/?ref=bref" />
                <PremiumSponsor src={myBuilderLogo} alt="MyBuilder" href="https://www.mybuilder.com/?ref=bref.sh" />
                <PremiumSponsor src={shippyProLogo} alt="ShippyPro" href="https://www.shippypro.com/?ref=bref.sh" />
                <PremiumSponsor src={nullLogo} alt="Null - Serverless consulting company" href="https://null.tc/?ref=bref" />
                <PremiumSponsor src={awsLogo} alt="AWS" href="https://aws.amazon.com" oneTime={true} />
            </div>

            <h3 className="mt-8 text-lg font-bold text-gray-700">
                Gold sponsors
            </h3>
            <div className="mt-4 -mx-6 grid grid-cols-3 gap-0.5 overflow-hidden sm:mx-0 sm:rounded-2xl md:grid-cols-5">
                <GoldSponsor src={jetbrainsLogo} alt="JetBrains - Maker of PhpStorm" href="https://www.jetbrains.com/?ref=bref.sh" imgClass="-my-4 max-h-16" />
                <GoldSponsor src={laravelLogo} alt="Laravel" href="https://laravel.com/?ref=bref.sh" />
                <GoldSponsor src={depotLogo} alt="Depot" href="https://depot.dev/?ref=bref.sh" imgClass="py-0.5" />
                <GoldSponsor src={secumailerLogo} alt="SecuMailer" href="https://secumailer.com/?ref=bref.sh" imgClass="py-1.5" />
                <GoldSponsor src={ecomailLogo} alt="Ecomail" href="https://ecomail.cz/?ref=bref.sh" imgClass="py-3" />
            </div>

            <p className="mt-8 text-gray-700">
                <a href="https://github.com/sponsors/mnapoli" className="text-blue-600 font-semibold underline">Become a sponsor</a> and help Bref be a sustainable open-source project.
            </p>
        </div>
    );
}
