// Random number generator.
// Mersenne Twister.

#define N 624
#define M 397
#define MATRIX_A 0x9908b0dfUL
#define UPPER_MASK 0x80000000UL
#define LOWER_MASK 0x7fffffffUL

static unsigned long mt[N];
static int mti = N + 1;

void init_genrand(unsigned long s)
{
    mt[0] = s & 0xffffffffUL;
    for (mti = 1; mti < N; mti++) {
        mt[mti] =
            (1812433253UL * (mt[mti - 1] ^ (mt[mti - 1] >> 30)) + mti);
        mt[mti] &= 0xffffffffUL;
    }
}

unsigned long genrand_int32(void)
{
    unsigned long y;
    static unsigned long mag01[2] = { 0x0UL, MATRIX_A };

    if (mti >= N) {
        int kk;

        if (mti == N + 1)
            init_genrand(5489UL);

        for (kk = 0; kk < N - M; kk++) {
            y = (mt[kk] & UPPER_MASK) | (mt[kk + 1] & LOWER_MASK);
            mt[kk] = mt[kk + M] ^ (y >> 1) ^ mag01[y & 0x1UL];
        }
        for (; kk < N - 1; kk++) {
            y = (mt[kk] & UPPER_MASK) | (mt[kk + 1] & LOWER_MASK);
            mt[kk] = mt[kk + (M - N)] ^ (y >> 1) ^ mag01[y & 0x1UL];
        }
        y = (mt[N - 1] & UPPER_MASK) | (mt[0] & LOWER_MASK);
        mt[N - 1] = mt[M - 1] ^ (y >> 1) ^ mag01[y & 0x1UL];

        mti = 0;
    }

    y = mt[mti++];

    y ^= (y >> 11);
    y ^= (y << 7) & 0x9d2c5680UL;
    y ^= (y << 15) & 0xefc60000UL;
    y ^= (y >> 18);

    return y;
}

double genrand_real1(void) { return genrand_int32() * (1.0 / 4294967295.0); }
double genrand_real2(void) { return genrand_int32() * (1.0 / 4294967296.0); }

// Initiate pseudorandom sequence.
void MySrand(unsigned long seed)
{
    init_genrand(seed);
    //srand (seed);
}

// Return a random number between a and b (including a and b)
unsigned long MyRand(unsigned long a, unsigned long b)
{
    return a + (unsigned long)(genrand_real1() * (b - a + 1));
    //return a + (unsigned long)((rand ()*(1.0/RAND_MAX)) * (b - a + 1));
}