import { CloudWatchClient, GetMetricDataCommand } from '@aws-sdk/client-cloudwatch';
import { unstable_cache } from 'next/cache';

/**
 * Cached, and the page that uses this data (the home page) is refreshed every hour.
 *
 * @returns {Promise<number>}
 */
export const getBrefInvocations = unstable_cache(fetchBrefInvocations, ['bref-invocations'], {
    revalidate: 60 * 60,
});

async function fetchBrefInvocations() {
    const cloudWatch = new CloudWatchClient({
        region: 'us-east-1'
    });

    const response = await cloudWatch.send(new GetMetricDataCommand({
        // 30 days ago
        StartTime: new Date(Date.now() - 1000 * 60 * 60 * 24 * 30),
        // Now
        EndTime: new Date(),
        MetricDataQueries: [
            {
                Id: 'invocations',
                MetricStat: {
                    Metric: {
                        Namespace: 'Bref/Stats',
                        MetricName: 'Invocations_100'
                    },
                    Period: 60 * 60 * 24 * 30, // the whole month
                    Stat: 'Sum',
                    Unit: 'Count'
                }
            }
        ]
    }));
    // If we are between 2 months, it will return 1 value per month so we must deal with that
    const invocationMetrics = response.MetricDataResults[0].Values ? response.MetricDataResults[0].Values : [];
    let invocations = invocationMetrics.reduce((a, b) => a + b, 0);
    // We must multiply by 100 because the metric is collected every 100 invocations
    invocations *= 100;
    return invocations;
}
